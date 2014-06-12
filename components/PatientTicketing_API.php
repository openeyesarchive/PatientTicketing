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

namespace OEModule\PatientTicketing\components;


use OEModule\PatientTicketing\models\Queue;
use OEModule\PatientTicketing\models\Ticket;

class PatientTicketing_API extends \BaseAPI
{
	/**
	 * Simple function to standardise access to the retrieving the Queue Assignment Form
	 *
	 * @return string
	 */
	public function getQueueAssignmentFormURI()
	{
		return "/PatientTicketing/Default/GetQueueAssignmentForm/";
	}

	/**
	 * @param $event
	 * @return mixed
	 */
	public function getTicketForEvent($event)
	{
		return Ticket::model()->findByAttributes(array('event_id' => $event->id));
	}

	/**
	 * Filters and purifies passed array to get data relevant to a ticket queue assignment
	 *
	 * @param \OEModule\PatientTicketing\models\Queue $queue
	 * @param $data
	 * @param bool $validate
	 * @return array
	 */
	public function extractQueueData(Queue $queue, $data, $validate = false)
	{
		$res = array();
		$errs = array();
		$p = new \CHtmlPurifier();

		foreach ($queue->getFormFields() as $field_name => $required) {
			$res[$field_name] = $p->purify(@$data[$field_name]);
			if ($validate && $required && !@$data[$field_name]) {
				$errs[$field_name] = $queue->getAttributeLabel($field_name) . " is required";
			}
		}

		if ($validate) {
			return array($res, $errs);
		}
		else {
			return $res;
		}
	}

	/**
	 *
	 * @param \Event $event
	 * @param Queue $initial_queue
	 * @param \CWebUser $user
	 * @param \Firm $firm
	 * @throws \Exception
	 * @return \OEModule\PatientTicketing\models\Ticket
	 */
	public function createTicketForEvent(\Event $event, Queue $initial_queue, \CWebUser $user, \Firm $firm, $data)
	{
		$patient = $event->episode->patient;
		if ($ticket = $this->createTicketForPatient($patient, $initial_queue, $user, $firm, $data)) {
			$ticket->event_id = $event->id;
			$ticket->save();
		}
		else {
			throw new \Exception('Ticket was not created for an unknown reason');
		}

		return $ticket;
	}

	/**
	 * @TODO: fix the priority assignment for the ticket
	 * @param \Patient $patient
	 * @param Queue $initial_queue
	 * @param \CWebUser $user
	 * @param \Firm $firm
	 * @return \OEModule\PatientTicketing\models\Ticket
	 */
	public function createTicketForPatient(\Patient $patient, Queue $initial_queue, \CWebUser $user, \Firm $firm, $data)
	{
		$ticket = new Ticket();
		$ticket->patient_id = $patient->id;
		$ticket->created_user_id = $user->id;
		$ticket->last_modified_user_id = $user->id;
		$ticket->priority_id = $data['patientticketing__priority'];
		$ticket->save();

		$initial_queue->addTicket($ticket, $user, $firm, $data);
		return $ticket;
	}

	/**
	 * Verifies that the provided queue id is an id for a Queue that the User can add to as the given Firm
	 * At the moment, no verification takes place beyond the fact that the id is a valid one
	 *
	 * @param \User $user
	 * @param \Firm $firm
	 * @param integer $id
	 */
	public function getQueueForUserAndFirm(\CWebUser $user, \Firm $firm, $id)
	{
		return Queue::model()->findByPk($id);
	}
}