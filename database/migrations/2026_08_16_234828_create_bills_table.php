<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description', 255)->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->unsignedSmallInteger('installments');
            $table->decimal('installment_amount', 12, 2);
            $table->unsignedTinyInteger('due_day')->default(1)->comment('Día del mes en que vence la cuota');
            $table->date('start_date');
            $table->unsignedSmallInteger('paid_installments')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
