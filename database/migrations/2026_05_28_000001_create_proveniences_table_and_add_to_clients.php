<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProveniencesTableAndAddToClients extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proveniences')) {
            Schema::create('proveniences', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->index();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'provenience_id')) {
                $table->unsignedBigInteger('provenience_id')->nullable()->after('company_country_id');
                $table->foreign('provenience_id')->references('id')->on('proveniences')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'provenience_id')) {
                $table->dropForeign(['provenience_id']);
                $table->dropColumn('provenience_id');
            }
        });

        Schema::dropIfExists('proveniences');
    }
}
