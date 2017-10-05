<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
				$table->string('email', 100)->unique();
				$table->string('name', 100)->nullable();
				$table->string('password');
				$table->string('app_password')->nullable();
				$table->string('phone', 45)->nullable();
				$table->date('birthday')->nullable();
				$table->string('token')->unique()->nullable();
				$table->string('app_token')->unique()->nullable();
				$table->rememberToken();
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
		Schema::dropIfExists('traffic');
		Schema::dropIfExists('permissions');
		Schema::dropIfExists('users');
	}
}
