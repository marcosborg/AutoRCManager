<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('suplier_id');
            $table->unsignedBigInteger('repair_id')->nullable();
            $table->date('order_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('suplier_id')->references('id')->on('supliers')->onDelete('cascade');
            $table->foreign('repair_id')->references('id')->on('repairs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_orders');
    }
};
