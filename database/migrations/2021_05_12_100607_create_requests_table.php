<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->integer('status',);
		$table->bigIncrements('id')->unsigned();
		$table->bigInteger('requester_id',20)->unsigned();
		$table->bigInteger('requested_item_id',20)->unsigned();
		$table->bigInteger('requester_item_id',20)->unsigned();
		$table->integer('rating',);
		$table->text('token');
		$table->primary('id');
		$table->foreign('requester_id')->references('id')->on('users');
      
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
        Schema::dropIfExists('requests');
    }
}
