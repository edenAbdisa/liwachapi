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
            $table->bigIncrements('id');
            $table->string('first_name',10)->nullable();
            $table->string('last_name',10)->nullable();        
            $table->string('email')->unique()->nullable();
            $table->text('password')->nullable();
            $table->text('profile_picture')->nullable();
            $table->string('phone_number',30)->nullable();
            $table->text('TIN_picture')->nullable();
            $table->string('status',10)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('type',10)->default('user')->nullable();
            $table->bigInteger('address_id')->unsigned()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->bigInteger('membership_id')->unsigned()->nullable();
            $table->rememberToken();
            $table->timestamps(); 
            $table->foreign('address_id')->references('id')->on('addresses');
            $table->foreign('membership_id')->references('id')->on('memberships');
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
