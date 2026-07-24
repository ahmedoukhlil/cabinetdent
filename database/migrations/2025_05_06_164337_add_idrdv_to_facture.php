<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('facture', function (Blueprint $table) {
            $table->unsignedInteger('IDRdv')->nullable()->after('fkidCabinet');
        });
    }

    public function down()
    {
        Schema::table('facture', function (Blueprint $table) {
            $table->dropColumn('IDRdv');
        });
    }
}; 