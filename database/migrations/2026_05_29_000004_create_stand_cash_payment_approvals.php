<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stand_cash_payment_approvals')) {
            return;
        }

        Schema::create('stand_cash_payment_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_client_payment_id')->unique();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->unsignedBigInteger('cash_operation_id')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_client_payment_id', 'stand_cash_payment_approvals_payment_foreign')
                ->references('id')
                ->on('vehicle_client_payments')
                ->cascadeOnDelete();
            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->cascadeOnDelete();
            $table->foreign('created_by_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('approved_by_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('cash_operation_id')
                ->references('id')
                ->on('account_operations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stand_cash_payment_approvals');
    }
};
