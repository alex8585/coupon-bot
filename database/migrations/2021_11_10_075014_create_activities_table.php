<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->integer('tguser_id')->index();
            $table->string('type');
            //$table->json('data')->nullable();
            $table->integer('coupon_id')->index()->nullable();
            $table->string('action')->nullable();

            $table->integer('shop_id')->index()->nullable();
            $table->integer('category_id')->index()->nullable();
            $table->integer('is_id')->index()->nullable();
            $table->integer('page')->nullable();

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
        Schema::dropIfExists('activities');
    }
}
