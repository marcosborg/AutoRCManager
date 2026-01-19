<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_consignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('from_unit_id');
            $table->unsignedBigInteger('to_unit_id');
            $table->decimal('reference_value', 12, 2);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('from_unit_id')->references('id')->on('operational_units')->restrictOnDelete();
            $table->foreign('to_unit_id')->references('id')->on('operational_units')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_consignments');
    }
};
