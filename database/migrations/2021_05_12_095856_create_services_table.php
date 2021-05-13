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
            $table->bigIncrements('id')->unsigned();
            $table->string('name',50);
            $table->text('picture');
            $table->string('status',10)->default('UNBARTERED');
            $table->integer('number_of_flag',);
            $table->integer('number_of_request',);
            $table->integer('bartering_location_id');
            $table->primary('id');
            $table->timestamps();
            $table->foreign('bartering_location_id')->references('id')->on('addresses');
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
