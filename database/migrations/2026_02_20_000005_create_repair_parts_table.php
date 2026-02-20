<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_parts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('repair_id');
            $table->string('supplier')->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('part_date')->nullable();
            $table->string('part_name')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('repair_id', 'repair_parts_repair_fk')
                ->references('id')
                ->on('repairs')
                ->onDelete('cascade');
            $table->index(['repair_id', 'part_date'], 'repair_parts_repair_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_parts');
    }
};

