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
            $table->bigIncrements('id')->unsigned()->primary();
		    $table->string('reason',50);
		    $table->bigInteger('flagged_item_id')->unsigned();
		    $table->string('type',30); 
            $table->timestamps();
            $table->foreign('reason')->references('report_detail')->on('report_types');
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
