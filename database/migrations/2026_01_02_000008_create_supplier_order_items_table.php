<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_order_id');
            $table->unsignedBigInteger('account_category_id');
            $table->string('item_name');
            $table->decimal('qty_ordered', 10, 2)->default(0);
            $table->decimal('qty_received', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('supplier_order_id')->references('id')->on('supplier_orders')->onDelete('cascade');
            $table->foreign('account_category_id')->references('id')->on('account_categories')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_order_items');
    }
};
