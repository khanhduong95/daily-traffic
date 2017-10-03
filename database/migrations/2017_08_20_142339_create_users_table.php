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
				$table->string('phone', 45)->nullable();
				$table->string('birthday', 11)->nullable();
				$table->string('api_token')->unique()->nullable();
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
