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
        Schema::create('recommendation_histories', function (Blueprint $table) {
            $table->id('recommendation_history_id');
            $table->string('nama_pelanggan');
            $table->string('nohp_pelanggan');
            $table->string('alamat_pelanggan');
            $table->text('kriteria_dipilih');
            $table->text('bobot_kriteria');
            $table->text('rekomendasi_data');
            $table->text('perhitungan_detail');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('recommendation_histories');
    }
};
