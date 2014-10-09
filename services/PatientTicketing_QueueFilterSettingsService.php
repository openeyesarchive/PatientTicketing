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
namespace OEModule\PatientTicketing\services;
use OEModule\PatientTicketing\models;
use Yii;

class PatientTicketing_QueueFilterSettingsService  extends \services\ModelService {

	static protected $operations = array(self::OP_CREATE, self::OP_READ, self::OP_SEARCH, self::OP_DELETE);
	static protected $primary_model = 'OEModule\PatientTicketing\models\QueueSetFilter';

	public function modelToResource($queue)
	{
		$res = parent::modelToResource($queue);
		foreach (array('id', 'patient_list', 'priority', 'subspecialty', 'firm', 'my_tickets', 'closed_tickets') as $pass_thru) {
			$res->$pass_thru = $queue->$pass_thru;
		}
		return $res;
	}

	protected function resourceToModel($resource, $model)
	{
		foreach (array('patient_list', 'priority', 'subspecialty', 'firm', 'my_tickets', 'closed_tickets') as $pass_thru) {
			$model->$pass_thru = $resource->$pass_thru;
		}
		$this->saveModel($model);
	}

}