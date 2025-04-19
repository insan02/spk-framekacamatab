<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->Integer('user_id');
            $table->string('user_name');
            $table->string('action'); // 'create', 'update', 'delete'
            $table->string('module'); // 'kriteria', 'subkriteria'
            $table->string('reference_id'); // ID dari kriteria/subkriteria
            $table->string('old_values')->nullable(); // nilai sebelum diubah (untuk update)
            $table->string('new_values')->nullable(); // nilai baru
            $table->text('description');
            $table->timestamps();
            
            $table->foreign('user_id')->references('user_id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};