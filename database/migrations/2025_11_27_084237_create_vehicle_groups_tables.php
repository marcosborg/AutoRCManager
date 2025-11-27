<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_group_vehicle', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_group_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_group_id')
                ->references('id')
                ->on('vehicle_groups')
                ->onDelete('cascade');
            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->onDelete('cascade');
            $table->primary(['vehicle_group_id', 'vehicle_id'], 'vg_vehicle_primary');
        });

        Schema::create('client_vehicle_group', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_group_id');
            $table->unsignedBigInteger('client_id');
            $table->foreign('vehicle_group_id')
                ->references('id')
                ->on('vehicle_groups')
                ->onDelete('cascade');
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
            $table->primary(['vehicle_group_id', 'client_id'], 'vg_client_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_vehicle_group');
        Schema::dropIfExists('vehicle_group_vehicle');
        Schema::dropIfExists('vehicle_groups');
    }
};
