<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->unsignedTinyInteger('month')->comment('1-12');
            $table->unsignedSmallInteger('year');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['bill_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
