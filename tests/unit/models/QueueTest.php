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

use OEModule\PatientTicketing\models;

class QueueTest extends \CDbTestCase {

	public $fixtures = array(
			'queues' => 'OEModule\PatientTicketing\models\Queue',
			'queue_outcomes' => 'OEModule\PatientTicketing\models\QueueOutcome',
			'queuesets' => 'OEModule\PatientTicketing\models\QueueSet',
	);

	public function dependentQueueIdsProvider()
	{
		return array(
			array(1, array(2,6,7,8,9,11)),
			array(2, array()),
			array(6, array(7,8,9,11)),
			array(5, array()),
			array(10, array()),
			array(7, array(8))
		);
	}

	/**
	 * @dataProvider dependentQueueIdsProvider
	 */
	public function testgetDependentQueueIds($id, $res)
	{
		$test = models\Queue::model()->findByPk($id);
		$output = $test->getDependentQueueIds();
		sort($output);
		$this->assertEquals($res, $output);
	}

	public function rootQueueProvider()
	{
		return array(
			array(6, 1),
			array(3, array(5,1)),
			array(8,1),
			array(10,10),
			array(4, array(5,1)),
			array(7, 1),
			array(11, 1)
		);
	}

	/**
	 * @dataProvider rootQueueProvider
	 */
	public function testgetRootQueue($id, $res)
	{
		$test = models\Queue::model()->findByPk($id);
		$output = $test->getRootQueue();

		if (is_array($res)) {
			$this->assertTrue(is_array($output), "array output expected for multiple queue roots.");
			$this->assertEquals(count($res), count($output));
			foreach ($output as $q) {
				$this->assertInstanceOf('OEModule\PatientTicketing\models\Queue', $q);
				$this->assertTrue(in_array($q->id, $res));
			}
		}
		else {
			$this->assertInstanceOf('OEModule\PatientTicketing\models\Queue', $output);
			$this->assertEquals($res, $output->id);
		}
	}

	public function queueSetProvider()
	{
		return array(
			array(1, 1),
			array(6, 1),
			array(12, 2),
			array(13, 2)
		);
	}

	/**
	 * @dataProvider queueSetProvider
	 */
	public function testgetQueueSet($id, $res)
	{
		$test = models\Queue::model()->findByPk($id);
		$qs = $test->getQueueSet();
		$this->assertEquals($res, $qs->id, "Incorrect QueueSet returned");
	}
}