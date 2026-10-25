<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->text('image_path')->nullable()->change();
            $table->string('source_id')->nullable()->unique();
            $table->string('market_hash_name')->nullable()->index();
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->text('image_path')->nullable()->change();
            $table->string('source_id')->nullable()->unique();
            $table->string('market_hash_name')->nullable()->index();
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->text('image_path')->nullable()->change();
            $table->string('source_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['source_id']);
            $table->dropIndex(['market_hash_name']);
            $table->dropColumn(['source_id', 'market_hash_name']);
            $table->string('image_path')->nullable()->change();
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->dropUnique(['source_id']);
            $table->dropIndex(['market_hash_name']);
            $table->dropColumn(['source_id', 'market_hash_name']);
            $table->string('image_path')->nullable()->change();
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropUnique(['source_id']);
            $table->dropColumn('source_id');
            $table->string('image_path')->nullable()->change();
        });
    }
};
