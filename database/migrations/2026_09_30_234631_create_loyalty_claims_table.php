<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loyalty_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            // Denormalized for fast tenant-scoped lookups by staff without a join.
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('redemption_code', 12)->unique();
            $table->string('status')->default('pending');
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_claims');
    }
};
