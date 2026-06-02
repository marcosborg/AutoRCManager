<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_closure_approvals')) {
            return;
        }

        Schema::create('sale_closure_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('closed_by_id')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->string('trigger_type', 50);
            $table->unsignedBigInteger('trigger_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('sales_total', 15, 2)->default(0);
            $table->decimal('client_payments_total', 15, 2)->default(0);
            $table->decimal('trade_ins_total', 15, 2)->default(0);
            $table->decimal('outstanding_amount', 15, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'closed_at']);
            $table->index(['vehicle_id', 'status']);
            $table->index(['closed_by_id', 'closed_at']);

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('closed_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_closure_approvals');
    }
};
