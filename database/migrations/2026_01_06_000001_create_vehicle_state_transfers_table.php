<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_state_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('from_general_state_id')->nullable();
            $table->unsignedBigInteger('to_general_state_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('fuel_level')->nullable();
            $table->json('snapshot');
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('from_general_state_id')->references('id')->on('general_states')->nullOnDelete();
            $table->foreign('to_general_state_id')->references('id')->on('general_states')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_state_transfers');
    }
};
