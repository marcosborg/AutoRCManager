<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_positions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tracker_id');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->unsignedSmallInteger('speed_kph');
            $table->boolean('fix_valid')->default(false);
            $table->decimal('voltage', 5, 2)->nullable();
            $table->dateTime('reported_at');
            $table->text('raw_data');
            $table->timestamps();

            $table->index('tracker_id');
            $table->index('reported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_positions');
    }
};
