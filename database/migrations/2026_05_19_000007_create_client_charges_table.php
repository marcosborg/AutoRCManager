<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_charges')) {
            Schema::create('client_charges', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->date('charged_at');
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
                $table->index(['client_id', 'charged_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_charges');
    }
};
