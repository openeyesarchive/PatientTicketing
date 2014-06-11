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
	protected $firm;

	/**
	 * Sets the firm property on the controller from the session
	 *
	 * @TODO: this is from BaseEventTypeController - it should be moved to BaseModuleController, and possibly even BaseController as firm
	 * is site wide, not limited to events
	 * @throws HttpException
	 */
	protected function setFirmFromSession()
	{
		if (!$firm_id = Yii::app()->session->get('selected_firm_id')) {
			throw new \HttpException('Firm not selected');
		}
		if (!$this->firm || $this->firm->id != $firm_id) {
			$this->firm = \Firm::model()->findByPk($firm_id);
		}
	}

	protected function beforeAction($action)
	{
		$this->setFirmFromSession();
		return parent::beforeAction($action);
	}

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
						'actions' => array('moveTicket', 'getQueueAssignmentForm'),
						'roles' => array('oprnEditPatientTicket'),
				),
		);
	}

	public function actionIndex()
	{
		// build criteria
		$criteria = new \CDbCriteria();

		// get tickets that match criteria
		$tickets = models\Ticket::model()->findAll($criteria);

		// render
		$this->render('ticketlist', array(
				'tickets' => $tickets,
			));
	}

	public function actionGetQueueAssignmentForm($id)
	{
		if (!$q = models\Queue::model()->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid queue id.');
		}

		$template_vars = array('queue' => $q);
		$p = new \CHtmlPurifier();

		foreach (array('label_width' => 2, 'field_width' => 8) as $id => $default) {
			$template_vars[$id] = @$_GET[$id] ? $p->purify($_GET[$id]) : $default;
		}

		$this->renderPartial('form_queueassign', $template_vars, false, false);
	}

	public function actionMoveTicket($id)
	{
		if (!$ticket = models\Ticket::model()->with('currentQueue')->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid ticket id.');
		}

		foreach(array('from_queue_id', 'to_queue_id') as $required_field) {
			if (!@$_POST[$required_field]) {
				throw new \CHttpException(400, "Missing required form field {$required_field}");
			}
		}

		if ($ticket->currentQueue->id != $_POST['from_queue_id']) {
			//TODO: handle this as a different response rather than exception
			throw new \CHttpException(409, "Ticket has already moved to a different queue");
		}

		if (!$to_queue = models\Queue::model()->findByPk($_POST['to_queue_id'])) {
			throw new \CHttpException(404, "Cannot find queue with id {$_POST['to_queue_id']}");
		}

		$api = Yii::app()->moduleAPI->get('PatientTicketing');
		list($data, $errs) = $api->extractQueueData($to_queue, $_POST, true);

		if (count($errs)) {
			//TODO: handle assignment form validation errors
			throw new \CHttpException(400, "Missing required field(s) " . implode(",", array_keys($errs)));
		}

		$to_queue->addTicket($ticket, Yii::app()->user, $this->firm, $data);

		echo "1";
	}

	public function actionGetTicketTableRow($id)
	{
		if (!$ticket = models\Ticket::model()->with('currentQueue')->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid ticket id.');
		}

		$this->renderPartial('_ticketlist_row', array(
					'ticket' => $ticket
				), false, false);
	}

	public function actionGetTicketTableRowHistory($id)
	{
		if (!$ticket = models\Ticket::model()->with(array('queue_assignments', 'queue_assignments.queue'))->findByPk($id)) {
			throw new \CHttpException(404, 'Invalid ticket id.');
		}

		$assignments = array();
		for ($i = 0; $i < count($ticket->queue_assignments) - 1; $i++) {
			$assignments[] = $ticket->queue_assignments[$i];
		}

		$this->renderPartial('_ticketlist_history', array(
					'ticket' => $ticket,
					'assignments' => $assignments
				), false, false);
	}

	public function actionTakeTicket($id)
	{
		if (!$ticket = models\Ticket::model()->with('currentQueue')->findByPk($id)) {
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
	 * @TODO: admin users should be able to release a ticket they don't own.
	 * @param $id
	 * @throws \CHttpException
	 */
	public function actionReleaseTicket($id)
	{
		if (!$ticket = models\Ticket::model()->with('currentQueue')->findByPk($id)) {
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