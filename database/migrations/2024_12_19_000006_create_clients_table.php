<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('vat')->nullable();
            $table->string('address')->nullable();
            $table->string('location')->nullable();
            $table->string('zip')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_vat')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_location')->nullable();
            $table->string('company_zip')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
