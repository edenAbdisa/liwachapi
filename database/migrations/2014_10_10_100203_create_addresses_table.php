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
            $table->string('country',50);
            $table->string('city',50);
            $table->string('subcity',50);
            $table->string('district',50);
            $table->string('landmark',50);
            $table->string('type',10);
            $table->bigIncrements('id')->unsigned()->primary();
            $table->timestamp('timestamp'); 
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
