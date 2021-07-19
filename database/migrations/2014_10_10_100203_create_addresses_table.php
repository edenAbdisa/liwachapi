<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('country', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('subcity', 50)->nullable();
            $table->string('district', 50)->nullable();
            $table->string('landmark', 50)->nullable();
            $table->string('type', 10)->nullable();
            $table->text('api')->nullable();
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
        Schema::dropIfExists('addresses');
    }
}
