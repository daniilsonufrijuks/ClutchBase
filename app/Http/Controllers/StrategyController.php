<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveStrategyRequest;
use App\Models\Guide;
use App\Models\MapAsset;
use App\Models\Strategy;
use App\Models\StrategyCollaborator;
use App\Models\StrategyFolder;
use App\Models\StrategyShare;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Accounts\AccountProvisioner;
use App\Services\Strategy\StrategyConflictException;
use App\Services\Strategy\StrategyWriter;
use App\Support\Seo;
use App\Support\Slugs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StrategyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureTeam($user);
        $memberships = TeamMember::query()->with('team')->where('user_id', $user->id)->get();
        $teamId = (int) $request->input('team', $memberships->first()->team_id);
        abort_unless($memberships->contains('team_id', $teamId) || $user->isAdmin(), 403);

        $strategies = Strategy::query()
            ->with(['map', 'editor', 'creator'])
            ->where('is_template', false)
            ->where('team_id', $teamId)
            ->when($request->filled('map'), fn ($query) => $query->whereHas('map', fn ($map) => $map->where('slug', $request->string('map'))))
            ->when($request->filled('side'), fn ($query) => $query->where('side', $request->string('side')))
            ->when($request->filled('objective'), fn ($query) => $query->where('objective', $request->string('objective')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Strategy/Playbook', [
            'strategies' => $strategies,
            'teams' => $memberships->map(fn (TeamMember $member) => [
                'id' => $member->team_id,
                'name' => $member->team->name,
                'role' => $member->role,
                'slug' => $member->team->slug,
            ]),
            'team_id' => $teamId,
            'folders' => StrategyFolder::query()->where('team_id', $teamId)->orderBy('name')->get(),
            'maps' => MapAsset::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['team', 'map', 'side', 'objective', 'status']),
            'objectives' => config('strategy.objectives'),
            'seo' => Seo::make('Playbook', 'Private team strategies.', url('/strategies'), 'noindex,nofollow'),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $this->ensureTeam($user);

        return Inertia::render('Strategy/Create', [
            'maps' => MapAsset::query()->where('is_active', true)->orderBy('name')->get(),
            'teams' => TeamMember::query()->with('team')->where('user_id', $user->id)->get()
                ->map(fn (TeamMember $member) => ['id' => $member->team_id, 'name' => $member->team->name, 'role' => $member->role]),
            'templates' => Strategy::query()->where('is_template', true)->where('status', 'active')->with('map')->get()
                ->map(fn (Strategy $strategy) => [
                    'id' => $strategy->id,
                    'title' => $strategy->title,
                    'side' => $strategy->side,
                    'objective' => $strategy->objective,
                    'map' => $strategy->map?->name,
                    'summary' => $strategy->summary,
                ]),
            'objectives' => config('strategy.objectives'),
            'disclaimer' => config('clutchbase.disclaimers.strategy'),
            'seo' => Seo::make('New strategy', 'Create a five-player plan.', url('/strategies/create'), 'noindex,nofollow'),
        ]);
    }

    public function store(Request $request, StrategyWriter $writer): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'map_asset_id' => ['required', 'exists:map_assets,id'],
            'team_id' => ['required', 'exists:teams,id'],
            'side' => ['required', 'in:t,ct'],
            'objective' => ['required', 'string', 'max:40'],
            'template_id' => ['nullable', 'exists:strategies,id'],
            'summary' => ['nullable', 'string', 'max:2000'],
        ]);

        $team = Team::query()->findOrFail($data['team_id']);
        Gate::authorize('view', $team);
        $strategy = $writer->create($request->user(), $data);

        return redirect()->route('strategies.edit', $strategy)->with('success', 'Strategy created.');
    }

    public function edit(Request $request, Strategy $strategy, StrategyWriter $writer): Response
    {
        Gate::authorize('view', $strategy);
        abort_if($strategy->is_template && ! $request->user()->isAdmin(), 403);

        return Inertia::render('Strategy/Edit', [
            'strategy' => $writer->graph($strategy),
            'can_edit' => $request->user()->can('update', $strategy),
            'roles' => config('strategy.roles'),
            'objectives' => config('strategy.objectives'),
            'utility' => config('strategy.utility'),
            'maps' => MapAsset::query()->where('is_active', true)->orderBy('name')->get(),
            'folders' => StrategyFolder::query()->where('team_id', $strategy->team_id)->orderBy('name')->get(),
            'guides' => Guide::query()->published()->get(['title', 'slug']),
            'seo' => Seo::make($strategy->title, 'Private strategy editor.', url('/strategies/'.$strategy->id), 'noindex,nofollow'),
        ]);
    }

    public function update(SaveStrategyRequest $request, Strategy $strategy, StrategyWriter $writer): RedirectResponse
    {
        $this->assertFolder($strategy, $request->input('folder_id'));

        try {
            $writer->save($strategy, $request->user(), $request->validated());
        } catch (StrategyConflictException) {
            throw ValidationException::withMessages([
                'revision' => 'This strategy was saved somewhere else. Reload the plan before saving again.',
            ])->status(409);
        }

        return back()->with('success', 'Strategy saved.');
    }

    public function duplicate(Request $request, Strategy $strategy, StrategyWriter $writer): RedirectResponse
    {
        Gate::authorize('duplicate', $strategy);
        $team = $this->primaryTeam($request->user());
        $copy = $writer->duplicate($strategy, $request->user(), $team);

        return redirect()->route('strategies.edit', $copy)->with('success', 'Strategy copied into your playbook.');
    }

    public function archive(Request $request, Strategy $strategy): RedirectResponse
    {
        Gate::authorize('archive', $strategy);
        $strategy->update([
            'status' => $strategy->status === 'archived' ? 'active' : 'archived',
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Strategy updated.');
    }

    public function share(Request $request, Strategy $strategy, StrategyWriter $writer): RedirectResponse
    {
        Gate::authorize('share', $strategy);
        $data = $request->validate([
            'permission' => ['required', 'in:view,edit'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
        $share = $writer->share($strategy, $request->user(), $data['permission'], $data['expires_at'] ?? null);

        return back()->with('share_url', $share['url'])->with('success', 'Share link created. Store it now; it is not shown again.');
    }

    public function revokeShare(Request $request, StrategyShare $share): RedirectResponse
    {
        Gate::authorize('share', $share->strategy);
        $share->update(['revoked_at' => now()]);

        return back()->with('success', 'Share link revoked.');
    }

    public function print(Request $request, Strategy $strategy, StrategyWriter $writer): Response
    {
        Gate::authorize('view', $strategy);

        return Inertia::render('Strategy/Print', [
            'strategy' => $writer->graph($strategy),
            'seo' => Seo::make($strategy->title.' print', 'Printable strategy.', url('/strategies/'.$strategy->id.'/print'), 'noindex,nofollow'),
        ]);
    }

    public function export(Request $request, Strategy $strategy, StrategyWriter $writer): JsonResponse
    {
        Gate::authorize('view', $strategy);

        return response()->json([
            'disclaimer' => config('clutchbase.disclaimers.strategy'),
            'strategy' => $writer->graph($strategy),
        ])->header('Content-Disposition', 'attachment; filename="'.$strategy->slug.'.json"');
    }

    public function openShare(Request $request, string $token, StrategyWriter $writer): Response|RedirectResponse
    {
        $share = $writer->findShare($token);
        abort_unless($share, 404);
        $strategy = $share->strategy()->firstOrFail();

        if ($share->permission === 'edit') {
            if (! $request->user()) {
                return redirect()->guest('/login');
            }
            StrategyCollaborator::query()->updateOrCreate(
                ['strategy_id' => $strategy->id, 'user_id' => $request->user()->id],
                ['permission' => 'edit'],
            );

            return redirect()->route('strategies.edit', $strategy);
        }

        return Inertia::render('Strategy/Show', [
            'strategy' => $writer->graph($strategy),
            'token' => $token,
            'can_edit' => false,
            'seo' => Seo::make($strategy->title, 'Shared strategy.', url('/s/'.$token), 'noindex,nofollow'),
        ]);
    }

    public function duplicateShare(Request $request, string $token, StrategyWriter $writer): RedirectResponse
    {
        $share = $writer->findShare($token);
        abort_unless($share, 404);
        $copy = $writer->duplicate($share->strategy, $request->user(), $this->primaryTeam($request->user()));

        return redirect()->route('strategies.edit', $copy)->with('success', 'Shared strategy copied into your playbook.');
    }

    public function storeTeam(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $team = Team::query()->create([
            'owner_id' => $request->user()->id,
            'name' => $data['name'],
            'slug' => Slugs::unique(Team::class, $data['name']),
        ]);
        TeamMember::query()->create(['team_id' => $team->id, 'user_id' => $request->user()->id, 'role' => 'captain']);

        return back()->with('success', 'Team created.');
    }

    public function storeFolder(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manage', $team);
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        StrategyFolder::query()->create(['team_id' => $team->id, 'name' => $data['name']]);

        return back()->with('success', 'Folder created.');
    }

    public function invite(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manage', $team);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:captain,member'],
        ]);
        $existing = User::query()->whereRaw('lower(email) = ?', [mb_strtolower($data['email'])])->first();
        if ($existing) {
            TeamMember::query()->updateOrCreate(
                ['team_id' => $team->id, 'user_id' => $existing->id],
                ['role' => $data['role']],
            );

            return back()->with('success', 'Teammate added.');
        }

        $token = Str::random(48);
        TeamInvite::query()->create([
            'team_id' => $team->id,
            'email' => $data['email'],
            'token_hash' => hash('sha256', $token),
            'role' => $data['role'],
            'expires_at' => now()->addDays(7),
            'invited_by' => $request->user()->id,
        ]);

        return back()->with('share_url', url('/register?invite='.$token))->with('success', 'Invite created for a new account.');
    }

    private function ensureTeam(User $user): void
    {
        if ($user->memberships()->doesntExist()) {
            app(AccountProvisioner::class)->provision($user);
        }
    }

    private function primaryTeam(User $user): Team
    {
        $this->ensureTeam($user);

        return Team::query()->where('owner_id', $user->id)->first()
            ?? TeamMember::query()->where('user_id', $user->id)->firstOrFail()->team;
    }

    private function assertFolder(Strategy $strategy, mixed $folderId): void
    {
        if (! $folderId) {
            return;
        }

        $folder = StrategyFolder::query()->find($folderId);
        if (! $folder || $folder->team_id !== $strategy->team_id) {
            throw ValidationException::withMessages(['folder_id' => 'Choose a folder from this team.']);
        }
    }
}
