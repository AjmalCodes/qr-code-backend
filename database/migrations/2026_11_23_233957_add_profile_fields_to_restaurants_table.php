<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Short bio & location details
            $table->string('tagline', 160)->nullable()->after('description');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('country', 100)->nullable()->after('city');
            $table->text('google_maps_url')->nullable()->after('country');
            $table->decimal('latitude', 10, 7)->nullable()->after('google_maps_url');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            // Social links & contact
            $table->string('whatsapp_number', 30)->nullable()->after('phone');
            $table->string('website_url')->nullable()->after('secondary_color');
            $table->string('facebook_url')->nullable()->after('website_url');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->string('tiktok_url')->nullable()->after('instagram_url');
            $table->string('youtube_url')->nullable()->after('tiktok_url');
            $table->string('x_url')->nullable()->after('youtube_url');

            // Weekly timings (optional JSON)
            $table->json('opening_hours')->nullable()->after('x_url');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'tagline',
                'city',
                'country',
                'google_maps_url',
                'latitude',
                'longitude',
                'whatsapp_number',
                'website_url',
                'facebook_url',
                'instagram_url',
                'tiktok_url',
                'youtube_url',
                'x_url',
                'opening_hours',
            ]);
        });
    }
};