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

namespace OEModule\PatientTicketing\controllers;
use OEModule\PatientTicketing\models;
use Yii;

class AdminController extends \ModuleAdminController {

	protected function beforeAction($action)
	{
		if (parent::beforeAction($action)) {
			Yii::app()->assetManager->registerScriptFile('js/jquery.jOrgChart.js', $this->assetPathAlias, 12);
			Yii::app()->assetManager->registerCssFile('css/jquery.jOrgChart.css', $this->assetPathAlias, 12);
			return true;
		}
	}

	/**
	 * Define the actions limited to POST requests
	 *
	 * @return array
	 */
	public function filters() {
		return array('postOnly + activateQueue, deactivateQueue, deleteQueue');
	}

	/**
	 * Render the main admin screen
	 */
	public function actionIndex()
	{
		$criteria = new \CDbCriteria();
		$criteria->addColumnCondition( array('is_initial' => true));

		$this->render('index', array('queues' => models\Queue::model()->findAll($criteria), 'title' => 'Queues'));
	}

	/**
	 * Create a new Queue with the optional given parent
	 *
	 * @param null $parent_id
	 * @throws \CHttpException
	 */
	public function actionAddQueue($parent_id = null)
	{
		$parent = null;
		$queue = new models\Queue();

		if ($parent_id) {
			if (!$parent = models\Queue::model()->findByPk($parent_id)) {
				throw new \CHttpException(404, "Queue not found with id {$parent_id}");
			}
			$queue->is_initial = false;
		}

		if (!empty($_POST)) {
			if (@$_POST['parent_id']) {
				if (!$parent = models\Queue::model()->findByPk($_POST['parent_id'])) {
					throw new \CHttpException(404, "Queue not found with id {$_POST['parent_id']}");
				}
			}
			$this->saveQueue($queue, $parent);
		}
		else {
			$this->renderPartial('form_queue', array(
						'parent' => $parent,
						'queue' => $queue,
						'errors' => null
					));
		}
	}

	/**
	 * Update the given Queue
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionUpdateQueue($id)
	{
		if (!$queue = models\Queue::model()->findByPk($id)) {
			throw new \CHttpException(404, "Queue not found with id {$id}");
		}

		if (!empty($_POST)) {
			$this->saveQueue($queue);
		}
		else {
			$this->renderPartial('form_queue', array(
						'parent' => null,
						'queue' => $queue,
						'errors' => null
					));
		}
	}

	/**
	 * Performs the update/create process on a Queue
	 *
	 * @param $queue
	 * @param null $parent
	 * @throws \CHttpException
	 */
	protected function saveQueue($queue, $parent = null)
	{
		// try and process form
		$queue->attributes = $_POST;
		if (!$queue->validate()) {
			$resp = array(
					'success' => false,
					'form' => $this->renderPartial('form_queue', array(
											'parent' => $parent,
											'queue' => $queue,
											'errors' => $queue->getErrors()
									), true)
			);
			echo \CJSON::encode($resp);
		}
		else {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$queue->save();
				if ($parent) {
					$outcome = new models\QueueOutcome();
					$outcome->queue_id = $parent->id;
					$outcome->outcome_queue_id = $queue->id;
					$outcome->save();
				}
				$transaction->commit();
				$resp = array('success' => true);
				echo \CJSON::encode($resp);
			}
			catch (Exception $e) {
				$transaction->rollback();
				throw new \CHttpException(500, "Unable to create queue");
			}
		}
	}

	/**
	 * Generates an HTML list layout of the given Queue and its Outcome Queues
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionLoadQueueAsList($id)
	{
		if (!$queue = models\Queue::model()->findByPk((int)$id)) {
			throw new \CHttpException(404, "Queue not found with id {$id}");
		}
		$root = $queue->getRootQueue();
		$resp = array(
				'rootid' => $root->id,
				'list' => $this->renderPartial("queue_as_list", array('queue' => $root), true)
		);
		echo \CJSON::encode($resp);
	}

	/**
	 * Marks the given Queue as active
	 *
	 * @throws \CHttpException
	 */
	public function actionActivateQueue()
	{
		if (!$queue = models\Queue::model()->findByPk((int)@$_POST['id'])) {
			throw new \CHttpException(404, "Queue not found with id {$id}");
		}
		$queue->active = true;
		if (!$queue->save()) {
			throw new \CHttpException(500, "Could not change queue state");
		}
		echo 1;
	}

	/**
	 * Marks the given Queue inactive
	 *
	 * @throws \CHttpException
	 */
	public function actionDeactivateQueue()
	{
		if (!$queue = models\Queue::model()->findByPk((int)@$_POST['id'])) {
			throw new \CHttpException(404, "Queue not found with id {$id}");
		}
		$transaction = Yii::app()->db->beginTransaction();
		try {
			$this->deactivateQueue($queue);
			$transaction->commit();
		}
		catch (Exception $e) {
			$transaction->rollback();
			throw new \CHttpException(500, "Could not change queue state");
		}
		echo 1;
	}

	/**
	 * Deactivate a Queue, and if $cascade is true, then deactivate it's children
	 *
	 * @param $queue
	 * @param bool $cascade
	 */
	protected function deactivateQueue($queue, $cascade = true)
	{
		$queue->active = false;
		if ($cascade) {
			foreach ($queue->outcome_queues as $oc) {
				$this->deactivateQueue($oc);
			}
		}
		$queue->save();
	}

	/**
	 * Determines whether the given Queue or its children (outcome queues) currently has tickets
	 * assigned to it.
	 *
	 * @TODO: this should be a ServiceLayer thing (as should various other bits in here)
	 * @param $queue
	 * @return bool
	 */
	protected function checkForTicketsOnQueue($queue)
	{
		if ($queue->getCurrentTickets()) {
			return true;
		}
		foreach ($queue->outcome_queues as $oq) {
			if ($this->checkForTicketsOnQueue($oq)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Retrieve the count of ticket assignments for the given Queue and whether it can be deleted
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionGetQueueTicketCount($id)
	{
		if (!$queue = models\Queue::model()->findByPk((int)$id)) {
			throw new \CHttpException(404, "Queue not found with id {$id}");
		}

		$criteria = new \CDbCriteria();
		$criteria->addColumnCondition(array('queue_id' => $queue->id));

		$resp = array(
				'current_count' => count($queue->getCurrentTickets()),
				'can_delete' => !$this->checkForTicketsOnQueue($queue));

		echo \CJSON::encode($resp);
	}

	/**
	 * Will only successfully delete a Queue if no ticket has ever been assigned to it, otherwise will throw
	 * an exception. Should only have been called when the values return by actionGetQueueTicketCount are zero
	 * @throws \Exception
	 * @throws Exception
	 * @throws \CHttpException
	 */
	public function actionDeleteQueue()
	{
		if (!$queue = models\Queue::model()->findByPk((int)@$_POST['id'])) {
			throw new \CHttpException(404, "Queue not found with id " . @$_POST['id']);
		}

		$transaction = Yii::app()->db->beginTransaction();
		try {
			if (models\QueueOutcome::model()->deleteAllByAttributes(array('outcome_queue_id' => $queue->id))
				&& $queue->delete()) {
				$transaction->commit();
				echo 1;
			}
			else {
				$transaction->rollback();
				echo 0;
			}
		}
		catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}
	}
}