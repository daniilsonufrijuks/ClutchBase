<?php

namespace App\Http\Requests;

use App\Models\Strategy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStrategyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $strategy = $this->route('strategy');

        return $strategy instanceof Strategy && ($this->user()?->can('update', $strategy) ?? false);
    }

    public function rules(): array
    {
        $unit = ['nullable', 'numeric', 'between:0,1'];

        return [
            'revision' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:120'],
            'map_asset_id' => ['required', 'exists:map_assets,id'],
            'folder_id' => ['nullable', 'exists:strategy_folders,id'],
            'side' => ['required', 'in:t,ct'],
            'objective' => ['required', 'string', 'max:40'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['private', 'team', 'link'])],
            'players' => ['required', 'array', 'size:5'],
            'players.*.slot' => ['required', 'integer', 'distinct', 'between:1,5'],
            'players.*.nickname' => ['required', 'string', 'max:40'],
            'players.*.role' => ['required', 'string', 'max:40'],
            'players.*.start_x' => $unit,
            'players.*.start_y' => $unit,
            'players.*.responsibilities' => ['nullable', 'string', 'max:2000'],
            'players.*.contingency' => ['nullable', 'string', 'max:2000'],
            'markers' => ['array', 'max:80'],
            'markers.*.kind' => ['required', Rule::in(config('strategy.markers'))],
            'markers.*.player_slot' => ['nullable', 'integer', 'between:1,5'],
            'markers.*.x' => ['required', 'numeric', 'between:0,1'],
            'markers.*.y' => ['required', 'numeric', 'between:0,1'],
            'markers.*.label' => ['nullable', 'string', 'max:80'],
            'markers.*.step_number' => ['nullable', 'integer', 'between:1,30'],
            'paths' => ['array', 'max:10'],
            'paths.*.player_slot' => ['required', 'integer', 'between:1,5'],
            'paths.*.label' => ['nullable', 'string', 'max:80'],
            'paths.*.points' => ['required', 'array', 'min:2', 'max:30'],
            'paths.*.points.*.x' => ['required', 'numeric', 'between:0,1'],
            'paths.*.points.*.y' => ['required', 'numeric', 'between:0,1'],
            'utility' => ['array', 'max:40'],
            'utility.*.player_slot' => ['required', 'integer', 'between:1,5'],
            'utility.*.utility_type' => ['required', Rule::in(array_keys(config('strategy.utility')))],
            'utility.*.throw_x' => $unit,
            'utility.*.throw_y' => $unit,
            'utility.*.land_x' => $unit,
            'utility.*.land_y' => $unit,
            'utility.*.timing_seconds' => ['required', 'integer', 'between:0,180'],
            'utility.*.step_number' => ['nullable', 'integer', 'between:1,30'],
            'utility.*.notes' => ['nullable', 'string', 'max:1000'],
            'utility.*.lineup_reference' => ['nullable', 'string', 'max:160'],
            'timeline' => ['array', 'max:30'],
            'timeline.*.seconds' => ['required', 'integer', 'between:0,180'],
            'timeline.*.title' => ['required', 'string', 'max:120'],
            'timeline.*.instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
