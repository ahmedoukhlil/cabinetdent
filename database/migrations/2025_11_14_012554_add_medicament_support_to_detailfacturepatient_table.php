<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detailfacturepatient', function (Blueprint $table) {
            if (!Schema::hasColumn('detailfacturepatient', 'fkidmedicament')) {
                $table->unsignedInteger('fkidmedicament')->nullable()->after('fkidacte');
            }
            // IsAct sera utilisé ainsi :
            // 1 = Acte (fkidacte utilisé)
            // 2 = Médicament (fkidmedicament utilisé, fkidtype=1)
            // 3 = Analyse (fkidmedicament utilisé, fkidtype=2)
            // 4 = Radio (fkidmedicament utilisé, fkidtype=3)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detailfacturepatient', function (Blueprint $table) {
            if (Schema::hasColumn('detailfacturepatient', 'fkidmedicament')) {
                $table->dropColumn('fkidmedicament');
            }
        });
    }
};
