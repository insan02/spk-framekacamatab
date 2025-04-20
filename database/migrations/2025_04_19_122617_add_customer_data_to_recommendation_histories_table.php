<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerDataToRecommendationHistoriesTable extends Migration
{
    public function up()
    {
        Schema::table('recommendation_histories', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->text('customer_address')->nullable()->after('customer_phone');
        });
    }

    public function down()
    {
        Schema::table('recommendation_histories', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_phone', 'customer_address']);
        });
    }
}