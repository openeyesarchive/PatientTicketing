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


class Queue extends \BaseActiveRecordVersioned
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return OphTrOperationnote_GlaucomaTube_PlatePosition the static model class
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
		return 'patientticketing_queue';
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
			'outcomes' => array(self::HAS_MANY, 'OEModule\PatientTicketing\models\QueueOutcome', 'queue_id'),
			'outcome_queues' => array(self::HAS_MANY, 'OEModule\PatientTicketing\models\Queue', 'outcome_queue_id', 'through' => 'outcomes')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
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

	/**
	 * Add the given ticket to the Queue for the user and firm
	 * @TODO: handle note data
	 * @TODO: handle 'details' field, which I think is going to be json blob of some kind (tbc)
	 *
	 * @param Ticket $ticket
	 * @param \CWebUser $user
	 * @param \Firm $firm
	 */
	public function addTicket(Ticket $ticket, \CWebUser $user, \Firm $firm, $data)
	{
		$ass = new TicketQueueAssignment();
		$ass->queue_id = $this->id;
		$ass->ticket_id = $ticket->id;
		$ass->assignment_user_id = $user->id;
		$ass->assignment_firm_id = $firm->id;
		$ass->assignment_date = date('Y-m-d H:i:s');
		$ass->notes = @$data['patientticketing__notes'];
		$ass->save();
	}

	/**
	 * Get simple data structure of possible outcomes for this Queue
	 *
	 * @param bool $json
	 * @return array|string
	 */
	public function getOutcomeData($json = true)
	{
		$res = array();
		foreach ($this->outcome_queues as $q) {
			$res[] = array('id' => $q->id, 'name' => $q->name);
		}
		if ($json) {
			return \CJSON::encode($res);
		}
		return $res;
	}

	/**
	 * Function to return a list of the fields that we are expecting an assignment form to contain for this queue
	 *
	 * @return array(fieldname => required)
	 */
	public function getFormFields()
	{
		$flds = array();
		$prefix = "patientticketing_";

		// priority and notes are reserved fields and so get additional _ prefix for the field name
		if ($this->is_initial) {
			$flds["{$prefix}_priority"] = true;
		}
		$flds["{$prefix}_notes"] = false;

		return $flds;
	}
}