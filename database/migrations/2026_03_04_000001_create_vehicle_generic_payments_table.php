<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_generic_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->string('expense_label');
            $table->date('paid_at');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('restrict');
            $table->index(['vehicle_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_generic_payments');
    }
};
