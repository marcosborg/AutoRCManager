<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarTasksTable extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['completed_at', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_tasks');
    }
}
