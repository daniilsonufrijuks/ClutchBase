<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Services\Accounts\AccountProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAndSimulatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_ignores_a_posted_admin_role(): void
    {
        $this->post('/register', [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'user']);
    }

    public function test_favorites_require_auth_and_toggle(): void
    {
        $item = Item::factory()->create();
        $this->post('/account/favorites', ['type' => 'item', 'slug' => $item->slug])->assertRedirect('/login');

        $user = User::factory()->create();
        $this->actingAs($user)->post('/account/favorites', ['type' => 'item', 'slug' => $item->slug])->assertRedirect();
        $this->assertDatabaseCount('favorites', 1);
        $this->actingAs($user)->post('/account/favorites', ['type' => 'item', 'slug' => $item->slug])->assertRedirect();
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_free_accounts_cannot_create_a_second_wishlist_or_an_alert(): void
    {
        $user = User::factory()->create();
        app(AccountProvisioner::class)->provision($user);
        $item = Item::factory()->create();

        $this->actingAs($user)->post('/account/wishlists', ['name' => 'Second'])->assertForbidden();
        $this->actingAs($user)->post('/account/alerts', [
            'slug' => $item->slug,
            'threshold' => '10',
            'direction' => 'below',
            'variant' => 'standard',
        ])->assertForbidden();
    }

    public function test_premium_accounts_can_export_and_save_alerts(): void
    {
        $user = User::factory()->premium()->create();
        app(AccountProvisioner::class)->provision($user);
        $item = Item::factory()->create();

        $this->actingAs($user)->post('/account/alerts', [
            'slug' => $item->slug,
            'threshold' => '10.50',
            'direction' => 'below',
            'variant' => 'standard',
        ])->assertRedirect();

        $this->assertDatabaseHas('price_alerts', ['user_id' => $user->id]);
    }

    public function test_account_pages_are_not_indexed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/account')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
