<?php

class m140616_085144_additional_fields extends CDbMigration
{
	public function up()
	{
		$this->addColumn('patientticketing_queue', 'assignment_fields', 'text');
		$this->addColumn('patientticketing_queue_version', 'assignment_fields', 'text');
	}

	public function down()
	{
		$this->dropColumn('patientticketing_queue_version', 'assignment_fields', 'text');
		$this->dropColumn('patientticketing_queue', 'assignment_fields', 'text');
	}
}