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
	);

	public function dependentQueueIdsProvider() {
		return array(
			array(1, array(2,6,7,8,9)),
			array(2, array()),
			array(6, array(7,8,9)),
			array(5, array()),
			array(10, array()),
			array(7, array(8))
		);
	}

	/**
	 * @dataProvider dependentQueueIdsProvider
	 */
	public function testgetDependentQueueIds($id, $res) {
		$test = models\Queue::model()->findByPk($id);
		$output = $test->getDependentQueueIds();
		sort($output);
		$this->assertEquals($res, $output);
	}
}