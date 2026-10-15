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
        Schema::create('loyalty_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_offer_id')->constrained()->cascadeOnDelete();
            // How many qualifying units this customer has bought toward this
            // offer since their last redemption — the "punch card" count.
            $table->unsignedInteger('progress_count')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'loyalty_offer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_progress');
    }
};
