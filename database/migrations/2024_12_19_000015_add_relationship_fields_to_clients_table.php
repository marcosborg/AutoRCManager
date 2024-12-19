<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToClientsTable extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id')->nullable();
            $table->foreign('country_id', 'country_fk_10171647')->references('id')->on('countries');
            $table->unsignedBigInteger('company_country_id')->nullable();
            $table->foreign('company_country_id', 'company_country_fk_10316255')->references('id')->on('countries');
        });
    }
}
