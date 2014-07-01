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

class DefaultController extends \BaseModuleController
{
	public $layout='//layouts/main';
	public $renderPatientPanel = false;
	protected $page_size = 10;

	/**
	 * Ensures firm is set on the controller.
	 *
	 * @param \CAction $action
	 * @return bool
	 */
	protected function beforeAction($action)
	{
		$this->setFirmFromSession();
		return parent::beforeAction($action);
	}

	/**
	 * List of print actions.
	 * @return array:
	 */
	public function printActions()
	{
		return array('printTickets');
	}

	/**
	 * Access rules for ticket actions
	 *
	 * @return array
	 */
	public function accessRules()
	{
		return array(
				array('allow',
						'actions' => array('index', 'getTicketTableRow', 'getTicketTableRowHistory'),
						'roles' => array('OprnViewPatientTickets'),
				),
				array('allow',
						'actions' => $this->printActions(),
						'roles' => array('OprnPrint'),
				),
				array('allow',
						'actions' => array('moveTicket', 'getQueueAssignmentForm', 'takeTicket', 'releaseTicket'),
						'roles' => array('OprnEditPatientTicket'),
				),
		);
	}

	/**
	 * Generate a list of current tickets
	 */
	public function actionIndex()
	{
		$filter_keys = array('queue-ids', 'priority-ids', 'subspecialty-id', 'firm-id', 'my-tickets', 'closed-tickets');
		$filter_options = array();

		if (empty($_POST)) {
			if ($filter_options = Yii::app()->session['patientticket_filter']) {
				foreach ($filter_options as $k => $v) {
					$_POST[$k] = $v;
				}
			}
		}
		else {
			foreach ($filter_keys as $k) {
				if (isset($_POST[$k])) {
					$filter_options[$k] = $_POST[$k];
				}
			}
		}

		Yii::app()->session['patientticket_filter'] = $filter_options;
		$patient_filter = null;
		// build criteria
		$criteria = new \CDbCriteria();

		if (@$_GET['patient_id']) {
			// this is a simple way of handling this for the sake of demo-ing functionality
			$criteria->addColumnCondition(array('patient_id' => $_GET['patient_id']));
			$patient_filter = \Patient::model()->findByPk($_GET['patient_id']);
		}
		else {
			// TODO: we probably don't want to have such a gnarly approach to this, we might want to denormalise so that we are able to do eager loading
			// That being said, we might get away with setting together false on the with to do this filtering (multiple query eager loading).
			$criteria->join = "JOIN " . models\TicketQueueAssignment::model()->tableName() . " cqa ON cqa.ticket_id = t.id and cqa.id = (SELECT id from " . models\TicketQueueAssignment::model()->tableName() . " qa2 WHERE qa2.ticket_id = t.id order by qa2.created_date desc limit 1)";

			// build queue id list
			$queue_ids = array();
			if (@$filter_options['queue-ids']) {
				$queue_ids = $filter_options['queue-ids'];
				if (@$filter_options['closed-tickets']) {
					// get all closed tickets regardless of whether queue is active or not
					foreach (models\Queue::model()->closing()->findAll() as $closed_queue) {
						$queue_ids[] = $closed_queue->id;
					}
				}
			}
			else {
				foreach (models\Queue::model()->active()->notClosing()->findAll() as $open_queue) {
					$queue_ids[] = $open_queue->id;
				}
				if (@$filter_options['closed-tickets']) {
					// get all closed tickets regardless of whether queue is active or not
					foreach (models\Queue::model()->closing()->findAll() as $active_queues) {
						$queue_ids[] = $active_queues->id;
					}
				}
			}

			if (@$filter_options['my-tickets']) {
				$criteria->addColumnCondition(array('assignee_user_id' => Yii::app()->user->id));
			}
			if (@$filter_options['priority-ids']) {
				$criteria->addInCondition('priority_id', $filter_options['priority-ids']);
			}
			if (count($queue_ids)) {
				$criteria->addInCondition('cqa.queue_id', $queue_ids);
			}
			if (@$filter_options['firm-id']) {
				$criteria->addColumnCondition(array('cqa.assignment_firm_id' => $filter_options['firm-id']));
			}
			elseif (@$filter_options['subspecialty-id']) {
				$criteria->join .= "JOIN " . \Firm::model()->tableName() . " f ON f.id = cqa.assignment_firm_id JOIN " . \ServiceSubspecialtyAssignment::model()->tableName() . " ssa ON ssa.id = f.service_subspecialty_assignment_id";
				$criteria->addColumnCondition(array('ssa.subspecialty_id' => $filter_options['subspecialty-id']));
			}
		}

		$criteria->order = 't.created_date desc';

		$count = models\Ticket::model()->count($criteria);
		$pages = new \CPagination($count);

		$pages->pageSize = $this->page_size;
		$pages->applyLimit($criteria);

		// get tickets that match criteria
		$tickets = models\Ticket::model()->findAll($criteria);

		// render
		$this->render('ticketlist', array(
				'tickets' => $tickets,
				'patient_filter' => $patient_filter,
				'pages' => $pages
			));
	}

	/**
	 * Generates the form for assigning a Ticket to the given Queue
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionGetQueueAssignmentForm($id)
	{
		if (!$q = models\Queue::model()->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid queue id.');
		}

		$template_vars = array('queue_id' => $id);
		$p = new \CHtmlPurifier();

		foreach (array('label_width' => 2, 'data_width' => 8) as $id => $default) {
			$template_vars[$id] = @$_GET[$id] ? $p->purify($_GET[$id]) : $default;
		}

		$this->renderPartial('form_queueassign', $template_vars, false, false);
	}

	/**
	 * Handles the moving of a ticket to a new Queue.
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionMoveTicket($id)
	{
		if (!$ticket = models\Ticket::model()->with('current_queue')->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid ticket id.');
		}

		foreach(array('from_queue_id', 'to_queue_id') as $required_field) {
			if (!@$_POST[$required_field]) {
				throw new \CHttpException(400, "Missing required form field {$required_field}");
			}
		}

		if ($ticket->current_queue->id != $_POST['from_queue_id']) {
			throw new \CHttpException(409, "Ticket has already moved to a different queue");
		}

		if (!$to_queue = models\Queue::model()->active()->findByPk($_POST['to_queue_id'])) {
			throw new \CHttpException(404, "Cannot find queue with id {$_POST['to_queue_id']}");
		}

		$api = Yii::app()->moduleAPI->get('PatientTicketing');
		list($data, $errs) = $api->extractQueueData($to_queue, $_POST, true);

		if (count($errs)) {
			echo json_encode(array("errors" => array_values($errs)));
			Yii::app()->end();
		}

		$transaction = Yii::app()->db->beginTransaction();

		try {
			if ($to_queue->addTicket($ticket, Yii::app()->user, $this->firm, $data)) {
				if ($ticket->assignee) {
					$ticket->assignee_user_id = null;
					$ticket->assignee_date = null;
					$ticket->save();
				}
				$transaction->commit();
			}
			else {
				throw new Exception("unable to assign ticket to queue");
			}
		}
		catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		echo "{}";
	}

	/**
	 * Generate individual row for the given Ticket id
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionGetTicketTableRow($id)
	{
		if (!$ticket = models\Ticket::model()->with('current_queue')->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid ticket id.');
		}

		$this->renderPartial('_ticketlist_row', array(
					'ticket' => $ticket
				), false, false);
	}

	/**
	 * Generate history rows for the given Ticket id
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionGetTicketTableRowHistory($id)
	{
		if (!$ticket = models\Ticket::model()->with(array('queue_assignments', 'queue_assignments.queue'))->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid ticket id.');
		}

		$this->renderPartial('_ticketlist_history', array(
					'ticket' => $ticket,
					'assignments' => $ticket->getPastQueueAssignments()
				), false, false);
	}

	/**
	 * Method to take ownership of a ticket for the current user
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionTakeTicket($id)
	{
		if (!$ticket = models\Ticket::model()->with('current_queue')->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid ticket id.');
		}

		$resp = array('status' => null);

		if ($ticket->assignee_user_id) {
			$resp['status'] = 0;
			if ($ticket->assignee_user_id != Yii::app()->user->id) {
				$resp['message'] = "Ticket has already been taken by " . $ticket->assignee->getFullName();
			}
			else {
				$resp['message'] = "Ticket was already taken by you.";
			}
		}
		else {
			$ticket->assignee_user_id = Yii::app()->user->id;
			$ticket->assignee_date = date('Y-m-d H:i:s');
			if ($ticket->save()) {
				$resp['status'] = 1;
			}
			else {
				$resp['status'] = 0;
				$resp['message'] = "Unable to take ticket at this time.";
				Yii::log("Couldn't save ticket to take it: " . print_r($ticket->getErrors(), true),\CLogger::LEVEL_ERROR);
			}
		}
		echo \CJSON::encode($resp);
	}

	/**
	 * Release a ticket from assignment
	 *
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionReleaseTicket($id)
	{
		if (!$ticket = models\Ticket::model()->with('current_queue')->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid ticket id.');
		}

		$resp = array('status' => null);
		if (!$ticket->assignee_user_id) {
			$resp['status'] = 0;
			$resp['message'] = "A ticket that is not owned cannot be released.";
		}
		elseif ($ticket->assignee_user_id != Yii::app()->user->id) {
			$resp['status'] = 0;
			$resp['message'] = "You cannot release a ticket you don't own.";
		}
		else {
			$ticket->assignee_user_id = null;
			$ticket->assignee_date = null;
			if ($ticket->save()) {
				$resp['status'] = 1;
			}
			else {
				$resp['status'] = 0;
				$resp['message'] = "Unable to release ticket at this time.";
				Yii::log("Couldn't save ticket to release it: " . print_r($ticket->getErrors(), true),\CLogger::LEVEL_ERROR);
			}
		}
		echo \CJSON::encode($resp);
	}
}
