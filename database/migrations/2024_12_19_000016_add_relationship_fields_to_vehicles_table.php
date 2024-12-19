<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToVehiclesTable extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->foreign('brand_id', 'brand_fk_10301148')->references('id')->on('brands');
            $table->unsignedBigInteger('seller_client_id')->nullable();
            $table->foreign('seller_client_id', 'seller_client_fk_10301156')->references('id')->on('clients');
            $table->unsignedBigInteger('buyer_client_id')->nullable();
            $table->foreign('buyer_client_id', 'buyer_client_fk_10301158')->references('id')->on('clients');
            $table->unsignedBigInteger('suplier_id')->nullable();
            $table->foreign('suplier_id', 'suplier_fk_10307303')->references('id')->on('supliers');
            $table->unsignedBigInteger('payment_status_id')->nullable();
            $table->foreign('payment_status_id', 'payment_status_fk_10307311')->references('id')->on('payment_statuses');
            $table->unsignedBigInteger('carrier_id')->nullable();
            $table->foreign('carrier_id', 'carrier_fk_10333797')->references('id')->on('carriers');
            $table->unsignedBigInteger('pickup_state_id')->nullable();
            $table->foreign('pickup_state_id', 'pickup_state_fk_10333803')->references('id')->on('pickup_states');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id', 'client_fk_10333812')->references('id')->on('clients');
        });
    }
}
