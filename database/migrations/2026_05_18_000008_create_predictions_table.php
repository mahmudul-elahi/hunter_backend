<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('scheduled_at');
            $table->integer('confidence_level');
            $table->enum('signal', ['home_win', 'away_win', 'draw', 'over', 'under']);
            $table->text('reason');
            $table->longText('detailed_summary')->nullable();
            $table->enum('status', ['active', 'win', 'loss', 'cancelled'])->default('active');
            $table->decimal('win_rate', 5, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
