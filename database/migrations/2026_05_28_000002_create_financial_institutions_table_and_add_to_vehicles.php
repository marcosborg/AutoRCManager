<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialInstitutionsTableAndAddToVehicles extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_institutions')) {
            Schema::create('financial_institutions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->index();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'financial_institution_id')) {
                $table->unsignedBigInteger('financial_institution_id')->nullable()->after('client_payment_method_info_id');
                $table->foreign('financial_institution_id')->references('id')->on('financial_institutions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'financial_institution_id')) {
                $table->dropForeign(['financial_institution_id']);
                $table->dropColumn('financial_institution_id');
            }
        });

        Schema::dropIfExists('financial_institutions');
    }
}
