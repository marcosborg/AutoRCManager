<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiclesTable extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('license')->nullable();
            $table->string('model')->nullable();
            $table->string('version')->nullable();
            $table->integer('year')->nullable();
            $table->string('vehicle_identification_number_vin')->nullable();
            $table->date('license_date')->nullable();
            $table->string('color')->nullable();
            $table->string('fuel')->nullable();
            $table->integer('kilometers')->nullable();
            $table->string('inspec_b')->nullable();
            $table->boolean('purchase_and_sale_agreement')->default(0)->nullable();
            $table->boolean('copy_of_the_citizen_card')->default(0)->nullable();
            $table->boolean('tax_identification_card')->default(0)->nullable();
            $table->boolean('copy_of_the_stamp_duty_receipt')->default(0)->nullable();
            $table->boolean('vehicle_registration_document')->default(0)->nullable();
            $table->boolean('vehicle_ownership_title')->default(0)->nullable();
            $table->boolean('vehicle_keys')->default(0)->nullable();
            $table->boolean('vehicle_manuals')->default(0)->nullable();
            $table->boolean('release_of_reservation_or_mortgage')->default(0)->nullable();
            $table->boolean('leasing_agreement')->default(0)->nullable();
            $table->boolean('cables')->default(0)->nullable();
            $table->date('date')->nullable();
            $table->longText('pending')->nullable();
            $table->longText('additional_items')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->longText('amount_paid')->nullable();
            $table->string('storage_location')->nullable();
            $table->string('withdrawal_authorization')->nullable();
            $table->date('withdrawal_authorization_date')->nullable();
            $table->date('pickup_state_date')->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->decimal('minimum_price', 15, 2)->nullable();
            $table->decimal('pvp', 15, 2)->nullable();
            $table->longText('client_amount_paid')->nullable();
            $table->string('client_registration')->nullable();
            $table->string('chekin_documents')->nullable();
            $table->date('chekin_date')->nullable();
            $table->date('sale_date')->nullable();
            $table->date('sele_chekout')->nullable();
            $table->string('first_key')->nullable();
            $table->string('scuts')->nullable();
            $table->string('key')->nullable();
            $table->string('manuals')->nullable();
            $table->string('elements_with_vehicle')->nullable();
            $table->longText('sale_notes')->nullable();
            $table->string('local')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
