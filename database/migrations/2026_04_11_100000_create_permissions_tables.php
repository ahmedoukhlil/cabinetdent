<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Table des permissions disponibles
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();         // ex: rendez-vous.create
            $table->string('label', 150);                  // ex: Créer des rendez-vous
            $table->string('groupe', 60);                  // ex: Rendez-vous
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });

        // Table pivot rôle ↔ permission
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedInteger('role_id');            // IdClasseUser (1,2,3)
            $table->unsignedInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
