<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->unsignedTinyInteger('biweek')->comment('1 o 2 (quincena)');
            $table->unsignedTinyInteger('month')->comment('1-12');
            $table->unsignedSmallInteger('year');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
