<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('status')->default('active')->after('logo');
            $table->string('primary_color', 7)->nullable()->after('status');
            $table->string('secondary_color', 7)->nullable()->after('primary_color');
        });

        // Backfill slugs for any pre-existing restaurants, then enforce uniqueness.
        DB::table('restaurants')->orderBy('id')->get(['id', 'name'])->each(function ($restaurant) {
            $base = Str::slug($restaurant->name) ?: 'restaurant';
            $slug = $base;
            $suffix = 1;

            while (DB::table('restaurants')->where('slug', $slug)->where('id', '!=', $restaurant->id)->exists()) {
                $slug = "{$base}-".(++$suffix);
            }

            DB::table('restaurants')->where('id', $restaurant->id)->update(['slug' => $slug]);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['slug', 'status', 'primary_color', 'secondary_color']);
        });
    }
};
