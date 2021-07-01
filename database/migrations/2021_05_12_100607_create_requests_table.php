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
            $table->bigIncrements('id');
            $table->string('status',10)->nullable(); 
		$table->bigInteger('requester_id')->unsigned()->nullable();
		$table->bigInteger('requested_item_id')->unsigned()->nullable();
		$table->bigInteger('requester_item_id')->unsigned()->nullable();
		$table->integer('rating')->nullable();
		$table->text('token')->unique()->nullable(); 
        $table->text('type')->default('item')->nullable();
		$table->foreign('requester_id')->references('id')->on('users');
        //$table->foreign('requester_id')->references('id')->on('users');
        //$table->foreign('requester_id')->references('id')->on('users');
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
