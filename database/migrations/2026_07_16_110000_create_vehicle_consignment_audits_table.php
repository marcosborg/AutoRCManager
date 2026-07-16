<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_consignment_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consignment_id')->nullable()->index();
            $table->string('action', 20)->index();
            $table->unsignedBigInteger('vehicle_id_before')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id_after')->nullable()->index();
            $table->string('vehicle_license_before')->nullable()->index();
            $table->string('vehicle_license_after')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_consignment_audits');
    }
};
