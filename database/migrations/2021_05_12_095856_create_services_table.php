<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',50);
            $table->text('picture');
            $table->string('status',10)->default('unbartered');
            $table->integer('number_of_flag',);
            $table->integer('number_of_request',);
            $table->bigInteger('bartering_location_id')->unsigned();
            $table->bigInteger('type_id')->unsigned();
            $table->timestamps();
            $table->foreign('bartering_location_id')->references('id')->on('addresses');
            $table->foreign('type_id')->references('id')->on('types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('services');
    }
}
