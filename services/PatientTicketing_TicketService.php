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

class PatientTicketing_TicketService extends \services\ModelService {

	static protected $primary_model = 'OEModule\PatientTicketing\models\Ticket';

	/**
	 * Pass through wrapper to generate Queue Resource
	 *
	 * @param OEModule\PatientTicketing\models\Ticket $ticket
	 * @return Resource
	 */
	public function modelToResource($ticket)
	{
		$res = parent::modelToResource($ticket);
		foreach (array('patient_id','priority_id','report','assignee_user_id','assignee_date',
			 'created_user_id','created_date','last_modified_user_id','last_modified_date','event_id') as $pass_thru) {
			$res->$pass_thru = $ticket->$pass_thru;
		}

		return $res;
	}

	/**
	 * @param models\Ticket $ticket
	 * @return array|mixed|null|string
	 */
	public function getTicketActionLabel(models\Ticket $ticket)
	{
		if (!$ticket->is_complete()) {
			if ($label = $ticket->current_queue->action_label) {
				return $label;
			}
			else {
				return "Move";
			}
		}
	}

}