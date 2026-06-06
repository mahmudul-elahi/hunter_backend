<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('revenuecat_app_user_id')->index();
            $table->string('revenuecat_original_app_user_id')->nullable();
            $table->string('revenuecat_product_id')->nullable()->index();
            $table->string('revenuecat_entitlement_id')->nullable()->index();
            $table->string('store')->nullable();
            $table->string('environment')->nullable();
            $table->string('status')->default('none')->index();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('billing_issue_at')->nullable();
            $table->json('raw_customer_info')->nullable();
            $table->string('last_event_id')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
