<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->enum('billing_period', ['monthly', 'yearly', 'half_yearly']);
            $table->text('description')->nullable();
            $table->json('features');
            $table->boolean('is_active')->default(true);
            $table->string('revenuecat_product_id')->unique();
            $table->string('revenuecat_entitlement_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
