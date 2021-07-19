<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('reason_id')->nullable();
            $table->bigInteger('flagged_item_id')->unsigned()->nullable();
            $table->bigInteger('flagged_by_id')->unsigned()->nullable();
            $table->string('type', 30)->default('item')->nullable();
            $table->timestamps();
            $table->foreign('reason_id')->references('id')->on('report_types');
            $table->foreign('flagged_by_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flags');
    }
}
