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

class PatientTicketing_QueueSetService extends \CDbTestCase
{
	public $fixtures = array(
			'patients' => 'Patient',
			'queues' => 'OEModule\PatientTicketing\models\Queue',
			'queue_outcomes' => 'OEModule\PatientTicketing\models\QueueOutcome',
			'queuesets' => 'OEModule\PatientTicketing\models\QueueSet',
			'tickets' => 'OEModule\PatientTicketing\models\Ticket',
			'ticketassignments' => 'OEModule\PatientTicketing\models\TicketQueueAssignment'
	);

	public function testgetQueueSetsForFirm()
	{
		$qs_svc = $this->getMockBuilder('OEModule\PatientTicketing\services\PatientTicketing_QueueSetService')
				->disableOriginalConstructor()
				->setMethods(array('modelToResource'))
				->getMock();

		$qs_svc->expects($this->at(0))
			->method('modelToResource')
			->with($this->queuesets('queueset1'));
		$qs_svc->expects($this->at(1))
				->method('modelToResource')
				->with($this->queuesets('queueset2'));

		$firm = new \Firm();

		$res = $qs_svc->getQueueSetsForFirm($firm);
		$this->assertEquals(2, count($res));
	}

	public function canAddPatientProvider()
	{
		return array(
				array('queueset1', 'patient1', false, "Patient is active on queueset so should not be add-able"),
				array('queueset2', 'patient1', true, "Patient has no ticket in queueset, so should be add-able"),
				array('queueset2', 'patient3', true, "Patient is complete on queueset, so should be add-able")
		);
	}

	/**
	 * @dataProvider canAddPatientProvider
	 */
	public function testcanAddPatientToQueueSet($qs_name, $patient_name, $res, $msg)
	{
		$queueset = $this->queuesets($qs_name);
		$qs_svc = Yii::app()->service->getService('PatientTicketing_QueueSet');
		$this->assertEquals($res, $qs_svc->canAddPatientToQueueSet($this->patients($patient_name), $queueset->id), $msg);
	}

}