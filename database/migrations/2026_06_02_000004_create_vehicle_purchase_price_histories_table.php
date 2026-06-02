<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_purchase_price_histories')) {
            return;
        }

        Schema::create('vehicle_purchase_price_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->decimal('previous_purchase_price', 15, 2)->nullable();
            $table->decimal('new_purchase_price', 15, 2);
            $table->decimal('sale_price', 15, 2);
            $table->string('reason', 120)->default('workshop_sale');
            $table->timestamps();

            $table->index(['vehicle_id', 'created_at']);
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('changed_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_purchase_price_histories');
    }
};
