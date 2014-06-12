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

namespace OEModule\PatientTicketing\models;

/**
 * This is the model class for table "patientticketing_ticket".
 *
 * The followings are the available columns in table:
 * @property string $id
 * @property integer $patient_id
 * @property integer $priority_id
 * @property string $report
 * @property integer $assignee_user_id
 * @property datetime $assignee_date
 * @property string $outcome TODO: do we still need this?
 * @property integer $created_user_id
 * @property datetime $created_date
 * @property integer $last_modified_user_id
 * @property datetime $last_modified_date
 * @property integer $event_id
 *
 *
 * The followings are the available model relations:
 *
 * @property \Event $event
 * @property \Patient $patient
 * @property Priority $priority
 * @property \User $assignee
 * @property \User $user
 * @property \User $usermodified
 */

class Ticket extends \BaseActiveRecordVersioned
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Ticket the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'patientticketing_ticket';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'created_user_id'),
			'usermodified' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
			'assignee' => array(self::BELONGS_TO, 'User', 'assignee_user_id'),
			'priority' => array(self::BELONGS_TO, 'OEModule\PatientTicketing\models\Priority', 'priority_id'),
			'patient' => array(self::BELONGS_TO, 'Patient', 'patient_id'),
			'event' => array(self::BELONGS_TO, 'Event', 'event_id'),
			'queue_assignments' => array(self::HAS_MANY, 'OEModule\PatientTicketing\models\TicketQueueAssignment', 'ticket_id'),
			'initial_queue_assignment' => array(self::HAS_ONE, 'OEModule\PatientTicketing\models\TicketQueueAssignment', 'ticket_id', 'order' => 'initial_queue_assignment.assignment_date'),
			'current_queue_assignment' => array(self::HAS_ONE, 'OEModule\PatientTicketing\models\TicketQueueAssignment', 'ticket_id', 'order' => 'current_queue_assignment.assignment_date desc'),
			'currentQueue' => array(self::HAS_ONE, 'OEModule\PatientTicketing\models\Queue', 'queue_id', 'through' => 'queue_assignments', 'order' => 'queue_assignments.assignment_date desc')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'event_id' => 'Source Event',
		);
	}

	public function behaviors()
	{
		return array(
				'LookupTable' => 'LookupTable',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id, true);

		return new CActiveDataProvider(get_class($this), array(
				'criteria' => $criteria,
		));
	}

	public function getSourceLink()
	{
		if ($this->event) {
			return "/" . $this->event->eventType->class_name . "/default/view/" . $this->event_id;
		}
		else {
			return "/patient/view/" . $this->patient_id;
		}
	}

	public function getSourceLabel()
	{
		if ($this->event) {
			return $this->event->eventType->name;
		}
		else {
			return 'Patient';
		}
	}

	public function getTicketFirm()
	{
		$ass = $this->initial_queue_assignment;
		return $ass->assignment_firm->getNameAndSubspecialty();
	}

	public function hasHistory()
	{
		return count($this->queue_assignments) > 1;
	}

	/**
	 * Get a data structure containing information about this ticket
	 *
	 * @param bool $json
	 * @return array|string
	 */
	public function getInfoData($json=true)
	{
		$res = array(
			'id' => $this->id,
			'patient_name' => $this->patient->getFullName(),
			'current_queue_name' => $this->currentQueue->name,
			'current_queue_id' => $this->currentQueue->id
		);
		if ($json) {
			return \CJSON::encode($res);
		}
		return $res;
	}

	/**
	 * Checks if the ticket is complete or not
	 *
	 * @return bool
	 */
	public function is_complete()
	{
		return count($this->currentQueue->outcomes) == 0;
	}

	/**
	 * Convenience function to access ticket notes.
	 *
	 * @return mixed
	 */
	public function getNotes()
	{
		return $this->current_queue_assignment->notes;
	}
}