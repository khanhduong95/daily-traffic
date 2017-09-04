<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrafficTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('traffic', function (Blueprint $table) {
				$table->bigIncrements('id');

				$table->dateTime('frequency');
				$table->index('frequency');

				$table->bigInteger('user_id')->unsigned();
				$table->foreign('user_id')
					->references('id')->on('users')
					->onUpdate('cascade')
					->onDelete('cascade');
				$table->index('user_id');

				$table->bigInteger('place_id')->unsigned();
				$table->foreign('place_id')
					->references('id')->on('places')
					->onUpdate('cascade')
					->onDelete('cascade');
				$table->index('place_id');

				$table->unique([
						'frequency', 
						'user_id',
						'place_id'
						]);
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
		Schema::drop('traffic');
	}
}
