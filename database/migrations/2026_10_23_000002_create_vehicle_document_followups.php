<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_document_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id')->index();
            $table->string('document', 80);
            $table->unsignedBigInteger('assigned_to_id');
            $table->string('contact');
            $table->date('requested_on');
            $table->date('next_contact_on');
            $table->text('note')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->unsignedBigInteger('media_id')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->json('history');
            $table->timestamps();
            $table->unique(['vehicle_id', 'document']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_document_followups');
    }
};
