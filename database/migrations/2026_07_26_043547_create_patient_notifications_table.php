<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id');
            $table->string('titre');
            $table->text('corps');
            $table->string('url')->nullable();
            $table->timestamp('lu_le')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('ID')->on('patients')->cascadeOnDelete();
            $table->index('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_notifications');
    }
};
