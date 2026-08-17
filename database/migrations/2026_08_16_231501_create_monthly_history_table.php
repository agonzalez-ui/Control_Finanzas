<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('salary', 12, 2)->default(0);
            $table->decimal('total_expenses', 12, 2)->default(0);
            $table->decimal('total_savings', 12, 2)->default(0);
            $table->decimal('total_subscriptions', 12, 2)->default(0);
            $table->timestamp('closed_at')->useCurrent();

            $table->unique(['user_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_history');
    }
};
