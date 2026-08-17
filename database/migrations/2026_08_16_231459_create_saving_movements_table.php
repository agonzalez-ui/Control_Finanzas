<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saving_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->comment('Positivo: depósito | Negativo: retiro');
            $table->string('description', 255)->nullable();
            $table->date('date');
            $table->unsignedTinyInteger('biweek')->default(1)->comment('1 o 2 (quincena)');
            $table->unsignedTinyInteger('month')->default(1)->comment('1-12');
            $table->unsignedSmallInteger('year')->default(2026);
            $table->boolean('affects_available')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_movements');
    }
};
