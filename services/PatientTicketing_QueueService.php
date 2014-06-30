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

class PatientTicketing_QueueService extends \services\ModelService {

	static protected $operations = array(self::OP_READ, self::OP_SEARCH, self::OP_DELETE);

	static protected $primary_model = 'OEModule\PatientTicketing\models\Queue';

	public function search(array $params)
	{
		$model = $this->getSearchModel();
		if (isset($params['id'])) $model->id = $params['id'];

		$searchParams = array('pageSize' => null);
		if (isset($params['name'])) $searchParams['name'] = $params['name'];

		return $this->getResourcesFromDataProvider($model->search($searchParams));
	}

	/**
	 * Pass through wrapper to generate Queue Resource
	 *
	 * @param \services\BaseActiveRecord $queue
	 * @return Resource
	 */
	public function modelToResource($queue)
	{
		$res = parent::modelToResource($queue);
		foreach (array('name', 'description', 'active', 'is_initial', 'summary_link') as $pass_thru) {
			$res->$pass_thru = $queue->$pass_thru;
		}
		if ($queue->assignment_fields) {
			$res->assignment_fields = \CJSON::decode($queue->assignment_fields);
		}
		return $res;
	}

	/**
	 * Wrapper to get the current ticket count for the Queue
	 *
	 * @param $queue_id
	 * @return mixed
	 */
	public function getCurrentTicketCount($queue_id)
	{
		$queue = $this->readModel($queue_id);
		return $queue->getCurrentTicketCount();
	}

	/**
	 * Check if the given Queue can be deleted (has no tickets assigned and no dependent queues with tickets)
	 *
	 * @param $queue_id
	 * @return bool
	 */
	public function canDeleteQueue($queue_id)
	{
		if ($this->getCurrentTicketCount($queue_id)) {
			return false;
		}
		$queue = $this->readModel($queue_id);
		foreach ($queue->getDependentQueueIds() as $dep_id) {
			if ($this->getCurrentTicketCount($dep_id)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Delete the queue and the queues that are are solely dependent on it.
	 *
	 * @param $queue_id
	 * @throws \Exception
	 * @throws Exception
	 */
	public function delete($queue_id)
	{
		$transaction = Yii::app()->db->getCurrentTransaction() === null
				? Yii::app()->db->beginTransaction()
				: false;

		try {
			$queue = $this->readModel($queue_id);
			// remove dependendent outcomes
			$remove_ids = $queue->getDependentQueueIds();
			$remove_ids[] = $queue_id;

			// how I'd do it if BaseActiveRecordVersioned supported delete with an in condition
			/*
			$criteria = new \CDbCriteria();
			$criteria->addInCondition('outcome_queue_id', $remove_ids);
			$criteria->addInCondition('queue_id', $remove_ids, 'OR');
			models\QueueOutcome::model()->deleteAll($criteria);

			// remove dependent and actual queues
			$criteria = new \CDbCriteria();
			$criteria->addInCondition($this->model->getPrimaryKey(), $remove_ids);
			$this->model->deleteAll($criteria);
			*/

			// instead ...
			foreach ($remove_ids as $rid) {
				$criteria = new \CDbCriteria();
				$criteria->addColumnCondition(array('outcome_queue_id' => $rid, 'queue_id' => $rid), 'OR');
				models\QueueOutcome::model()->deleteAll($criteria);
				$this->model->deleteByPk($rid);
			}

			if ($transaction) {
				$transaction->commit();
			}
		}
		catch (Exception $e) {
			if ($transaction) {
				$transaction->rollback();
			}
			throw $e;
		}
	}
}


