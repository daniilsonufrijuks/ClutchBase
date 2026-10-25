<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('password');
            $table->string('display_name')->nullable()->after('name');
            $table->char('currency', 3)->default('USD')->after('role');
            $table->json('preferences')->nullable()->after('currency');
        });

        Schema::create('rarities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('exteriors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('abbreviation', 8);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('weapons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 32);
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->index('category');
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->date('released_on')->nullable();
            $table->string('image_path')->nullable();
            $table->string('attribute_status', 20)->default('unverified');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->foreignId('weapon_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rarity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('min_float', 7, 6)->nullable();
            $table->decimal('max_float', 7, 6)->nullable();
            $table->boolean('has_stattrak')->nullable();
            $table->boolean('has_souvenir')->nullable();
            $table->text('pattern_note')->nullable();
            $table->date('released_on')->nullable();
            $table->string('update_name')->nullable();
            $table->string('team_side', 8)->nullable();
            $table->string('finish_style')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('attribute_status', 20)->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();
            $table->index(['status', 'type']);
            $table->index('name');
            $table->index('released_on');
        });

        Schema::create('item_exteriors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exterior_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->unique(['item_id', 'exterior_id']);
        });

        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->date('released_on')->nullable();
            $table->string('status', 20)->default('unknown');
            $table->string('category', 32)->default('case');
            $table->boolean('requires_key')->nullable();
            $table->decimal('key_amount', 12, 2)->nullable();
            $table->char('key_currency', 3)->default('USD');
            $table->boolean('key_is_demo')->default(false);
            $table->boolean('key_is_verified')->default(false);
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attribute_status', 20)->default('unverified');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();
            $table->index(['status', 'category']);
        });

        Schema::create('case_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('drop_group', 40)->nullable();
            $table->boolean('is_special')->default(false);
            $table->boolean('probability_known')->default(false);
            $table->unsignedInteger('probability_bps')->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'item_id']);
        });

        Schema::create('marketplaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver', 40);
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_status', 40)->nullable();
            $table->text('last_error')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->string('priceable_type', 20);
            $table->unsignedBigInteger('priceable_id');
            $table->foreignId('exterior_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant', 20)->default('standard');
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->timestamp('observed_at');
            $table->foreignId('data_source_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->index(['priceable_type', 'priceable_id', 'observed_at'], 'price_history_subject_idx');
        });

        Schema::create('item_prices', function (Blueprint $table) {
            $table->id();
            $table->string('priceable_type', 20);
            $table->unsignedBigInteger('priceable_id');
            $table->foreignId('exterior_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant', 20)->default('standard');
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->timestamp('observed_at');
            $table->foreignId('data_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_history_id')->nullable()->constrained('price_histories')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->index(['priceable_type', 'priceable_id', 'variant'], 'item_prices_lookup_idx');
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('favoritable_type', 20);
            $table->unsignedBigInteger('favoritable_id');
            $table->timestamps();
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'favorites_unique');
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->decimal('target_price', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['wishlist_id', 'item_id']);
        });

        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exterior_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant', 20)->default('standard');
            $table->decimal('threshold', 12, 2);
            $table->string('direction', 10)->default('below');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('simulated_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('simulated_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulated_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exterior_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant', 20)->default('standard');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('reference_amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_demo')->default(true);
            $table->timestamps();
        });

        Schema::create('case_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exterior_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant', 20)->default('standard');
            $table->decimal('reference_amount', 12, 2)->nullable();
            $table->boolean('probabilities_known')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 20);
            $table->unsignedBigInteger('subject_id');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 320)->nullable();
            $table->text('body');
            $table->boolean('is_published')->default(false);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();
        });

        Schema::create('featured_placements', function (Blueprint $table) {
            $table->id();
            $table->string('slot', 40);
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('subject_type', 20);
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();
            $table->index(['slot', 'position']);
        });

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->unsignedInteger('results_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable()->index();
            $table->index('term');
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('plan', 20)->default('free');
            $table->string('status', 20)->default('active');
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('query');
            $table->timestamps();
        });

        Schema::create('map_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image_path');
            $table->text('license_note');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('team_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->string('role', 20)->default('member');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('strategy_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('strategy_folders')->nullOnDelete();
            $table->foreignId('map_asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('side', 2);
            $table->string('objective', 40);
            $table->string('template_key')->nullable();
            $table->boolean('is_template')->default(false);
            $table->string('status', 20)->default('active');
            $table->string('visibility', 20)->default('private');
            $table->unsignedInteger('revision')->default(1);
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'status']);
            $table->index(['map_asset_id', 'side']);
        });

        Schema::create('strategy_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->string('nickname');
            $table->string('role', 40);
            $table->decimal('start_x', 7, 6)->nullable();
            $table->decimal('start_y', 7, 6)->nullable();
            $table->text('responsibilities')->nullable();
            $table->text('contingency')->nullable();
            $table->timestamps();
            $table->unique(['strategy_id', 'slot']);
        });

        Schema::create('strategy_markers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->unsignedTinyInteger('player_slot')->nullable();
            $table->decimal('x', 7, 6);
            $table->decimal('y', 7, 6);
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('step_number')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('strategy_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('player_slot');
            $table->string('label')->nullable();
            $table->json('points');
            $table->timestamps();
        });

        Schema::create('strategy_utility_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('player_slot');
            $table->string('utility_type', 20);
            $table->decimal('throw_x', 7, 6)->nullable();
            $table->decimal('throw_y', 7, 6)->nullable();
            $table->decimal('land_x', 7, 6)->nullable();
            $table->decimal('land_y', 7, 6)->nullable();
            $table->unsignedSmallInteger('timing_seconds')->default(0);
            $table->unsignedSmallInteger('step_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('lineup_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('strategy_timeline_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('seconds')->default(0);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('strategy_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('permission', 10)->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('strategy_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission', 10)->default('view');
            $table->timestamps();
            $table->unique(['strategy_id', 'user_id']);
        });

        Schema::create('strategy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('snapshot');
            $table->timestamp('created_at')->nullable();
            $table->index(['strategy_id', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_versions');
        Schema::dropIfExists('strategy_collaborators');
        Schema::dropIfExists('strategy_shares');
        Schema::dropIfExists('strategy_timeline_steps');
        Schema::dropIfExists('strategy_utility_actions');
        Schema::dropIfExists('strategy_paths');
        Schema::dropIfExists('strategy_markers');
        Schema::dropIfExists('strategy_players');
        Schema::dropIfExists('strategies');
        Schema::dropIfExists('strategy_folders');
        Schema::dropIfExists('team_invites');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('map_assets');
        Schema::dropIfExists('saved_filters');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('featured_placements');
        Schema::dropIfExists('guides');
        Schema::dropIfExists('slug_redirects');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('case_openings');
        Schema::dropIfExists('simulated_inventory_items');
        Schema::dropIfExists('simulated_inventories');
        Schema::dropIfExists('price_alerts');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('item_prices');
        Schema::dropIfExists('price_histories');
        Schema::dropIfExists('data_sources');
        Schema::dropIfExists('marketplaces');
        Schema::dropIfExists('case_contents');
        Schema::dropIfExists('cases');
        Schema::dropIfExists('item_exteriors');
        Schema::dropIfExists('items');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('weapons');
        Schema::dropIfExists('exteriors');
        Schema::dropIfExists('rarities');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'display_name', 'currency', 'preferences']);
        });
    }
};
