<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->primary();
            $table->string('first_name',10);
            $table->string('last_name',10);        
            $table->string('email')->unique();
            $table->text('password');
            $table->text('profile_picture');
            $table->string('phone_number',30);
            $table->text('TIN_picture');
            $table->string('status',10);
            $table->date('birthdate');
            $table->string('type',10)->default('USER');
            $table->bigInteger('address_id',20)->unsigned();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps(); 
            $table->foreign('address_id')->references('id')->on('addresses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
