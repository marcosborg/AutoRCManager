<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void { Schema::create('dismantled_parts',function(Blueprint $t){
  $t->id();$t->unsignedBigInteger('vehicle_id');$t->string('reference')->nullable();$t->string('description');
  $t->string('condition');$t->string('location');$t->decimal('value',12,2)->nullable();$t->text('notes')->nullable();
  $t->uuid('submission_token')->unique();$t->unsignedBigInteger('created_by_id');$t->timestamps();$t->index(['vehicle_id','reference']);
 }); }
 public function down():void { Schema::dropIfExists('dismantled_parts'); }
};
