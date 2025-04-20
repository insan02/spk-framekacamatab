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
        Schema::table('recommendation_histories', function (Blueprint $table) {
            // Remove the old columns
            $table->dropColumn(['nama_pelanggan', 'nohp_pelanggan', 'alamat_pelanggan']);
            
            // Add the new relationship column
            $table->unsignedBigInteger('customer_id')->after('recommendation_history_id');
            
            // Add foreign key constraint
            $table->foreign('customer_id')->references('customer_id')->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommendation_histories', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['customer_id']);
            
            // Drop the relationship column
            $table->dropColumn('customer_id');
            
            // Add back the old columns
            $table->string('nama_pelanggan');
            $table->string('nohp_pelanggan');
            $table->string('alamat_pelanggan');
        });
    }
};