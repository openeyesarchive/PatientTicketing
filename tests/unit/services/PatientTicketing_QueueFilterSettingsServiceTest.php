<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2014
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2014, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

use OEModule\PatientTicketing\services;

class PatientTicketing_QueueFilterSettingsServiceTest extends \CDbTestCase
{
	public $fixtures = array(
		'patients' => 'Patient',
		'queues' => 'OEModule\PatientTicketing\models\Queue',
		'queue_outcomes' => 'OEModule\PatientTicketing\models\QueueOutcome',
		'queuesets' => 'OEModule\PatientTicketing\models\QueueSet',
		'tickets' => 'OEModule\PatientTicketing\models\Ticket',
		'ticketassignments' => 'OEModule\PatientTicketing\models\TicketQueueAssignment',
		'filters' => 'OEModule\PatientTicketing\models\QueueSetFilter'
	);

	public function testGetFilterSettingsAllOn()
	{
		$svc = Yii::app()->service->getService('PatientTicketing_QueueFilterSettings');

		$this->assertEquals($svc->read(1)->patient_list,true);
		$this->assertEquals($svc->read(1)->priority,true);
		$this->assertEquals($svc->read(1)->subspecialty,true);
		$this->assertEquals($svc->read(1)->firm,true);
		$this->assertEquals($svc->read(1)->my_tickets,true);
		$this->assertEquals($svc->read(1)->closed_tickets,true);
	}

	public function testGetFilterSettingsAllOff()
	{
		$svc = Yii::app()->service->getService('PatientTicketing_QueueFilterSettings');

		$this->assertEquals($svc->read(2)->patient_list,false);
		$this->assertEquals($svc->read(2)->priority,false);
		$this->assertEquals($svc->read(2)->subspecialty,false);
		$this->assertEquals($svc->read(2)->firm,false);
		$this->assertEquals($svc->read(2)->my_tickets,false);
		$this->assertEquals($svc->read(2)->closed_tickets,false);
	}
}