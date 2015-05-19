<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */

	public function up()
	{
		Schema::create('tickets', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('title');
			$table->text('post');
			$table->integer('creator_id')->unsigned();
			$table->integer('assignee_id')->unsigned();
			$table->integer('status_id')->unsigned();
			$table->integer('priority_id')->unsigned();
			$table->integer('division_id')->unsigned();
			$table->integer('equipment_id')->unsigned();
			$table->integer('customer_id')->unsigned();
			$table->integer('contact_id')->unsigned()->nullable;		
			$table->timestamps();
		});

		Schema::table('tickets',function(Blueprint $table) {
			$table->foreign('creator_id')->references('id')->on('employees');
			$table->foreign('assignee_id')->references('id')->on('employees');
			$table->foreign('status_id')->references('id')->on('statuses');
			$table->foreign('priority_id')->references('id')->on('priorities');
			$table->foreign('division_id')->references('id')->on('divisions');
			$table->foreign('customer_id')->references('id')->on('customers');
			$table->foreign('contact_id')->references('id')->on('contacts');
		});		}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('tickets');
	}

}
