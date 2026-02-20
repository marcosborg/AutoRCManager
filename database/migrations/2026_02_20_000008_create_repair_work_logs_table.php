<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_work_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('repair_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('repair_id', 'repair_work_logs_repair_fk')
                ->references('id')
                ->on('repairs')
                ->onDelete('cascade');
            $table->foreign('user_id', 'repair_work_logs_user_fk')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->index(['repair_id', 'user_id'], 'repair_work_logs_repair_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_work_logs');
    }
};

