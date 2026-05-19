<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_payments')) {
            Schema::create('client_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('payment_method_id')->nullable();
                $table->date('paid_at');
                $table->decimal('amount', 15, 2);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
                $table->index(['client_id', 'paid_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payments');
    }
};
