<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('rendezvous', 'fkidfacture')) {
            Schema::table('rendezvous', function (Blueprint $table) {
                $table->unsignedInteger('fkidfacture')->nullable()->after('fkidcabinet');
            });
        }
    }

    public function down()
    {
        Schema::table('rendezvous', function (Blueprint $table) {
            $table->dropColumn('fkidfacture');
        });
    }
}; 