<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_subscription', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id')->unsigned()->nullable();
            $table->string('subscription_id')->unique();
            $table->string('customer_id');
            $table->string('product_id');
            $table->string('price_id');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_subscription');
    }
}
