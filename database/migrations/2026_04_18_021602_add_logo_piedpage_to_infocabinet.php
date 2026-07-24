<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogoPiedpageToInfocabinet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('infocabinet', function (Blueprint $table) {
            $table->string('logo', 500)->nullable()->after('DrFr');        // chemin relatif public/
            $table->text('piedPage')->nullable()->after('logo');            // texte pied de page imprimés
        });
    }

    public function down()
    {
        Schema::table('infocabinet', function (Blueprint $table) {
            $table->dropColumn(['logo', 'piedPage']);
        });
    }
}
