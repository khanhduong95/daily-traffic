<?php

use App\User;
use App\Place;
use App\Traffic;
use App\Permission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
	    $table->string('table_name');
	    $table->bigInteger('user_id')->unsigned();
	    $table->foreign('user_id')
		    ->references('id')->on('users')
		    ->onUpdate('cascade')
		    ->onDelete('cascade');
	    $table->index('user_id');

	    $table->boolean('write')->default(false);
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
        Schema::dropIfExists('permissions');
    }
}
