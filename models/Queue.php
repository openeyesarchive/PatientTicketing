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


use OEModule\PatientTicketing\components\Substitution;

/**
 * This is the model class for table "patientticketing_queue".
 *
 * The followings are the available columns in table:
 * @property string $id
 * @property string $name
 * @property string $description
 * @property boolean $active
 * @property string $report_definition
 * @property boolean $is_initial
 * @property boolean $summary_link - if true, tickets should link to the source event episode summary, rather than the event itself.
 * @property string assignment_fields
 * @property integer $created_user_id
 * @property datetime $created_date
 * @property integer $last_modified_user_id
 * @property datetime $last_modified_date
 *
 * The followings are the available model relations:
 *
 * @property \User $user
 * @property \User $usermodified
 * @property \OEModule\PatientTicketing\models\Outcome[] $outcomes
 * @property \OEModule\PatientTicketing\models\Queue[] $outcome_queues
 */
class Queue extends \BaseActiveRecordVersioned
{
	// used to prevent form field name conflicts
	protected static $FIELD_PREFIX = "patientticketing_";

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
			array('name', 'required')
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
	 *
	 * @param Ticket $ticket
	 * @param \CWebUser $user
	 * @param \Firm $firm
	 * @param $data
	 */
	public function addTicket(Ticket $ticket, \CWebUser $user, \Firm $firm, $data)
	{
		$ass = new TicketQueueAssignment();
		$ass->queue_id = $this->id;
		$ass->ticket_id = $ticket->id;
		$ass->assignment_user_id = $user->id;
		$ass->assignment_firm_id = $firm->id;
		$ass->assignment_date = date('Y-m-d H:i:s');
		$ass->notes = @$data[self::$FIELD_PREFIX . '_notes'];

		// store the assignment field values to the assignment object.
		if ($ass_flds = $this->getAssignmentFieldDefinitions()) {
			$details = array();
			foreach ($ass_flds as $ass_fld) {
				if ($val = @$data[$ass_fld['form_name']]) {
					if (@$ass_fld['choices']) {
						foreach ($ass_fld['choices'] as $k => $v) {
							if ($k == $val) {
								$val = $v;
								break;
							}
						}

					}
					$details[] = array(
						'id' => $ass_fld['id'],
						'value' => $val,
					);
				}
			}
			$ass->details = json_encode($details);
		}

		// generate the report field on the ticket.
		if ($this->report_definition) {
			$report = $ass->replaceAssignmentCodes($this->report_definition);
			$ticket->report = Substitution::replace($report, $ticket->patient);
			if (!$ticket->save()) {
				throw new Exception("Unable to update Ticket report field");
			}
		}
		if (!$ass->save()) {
			throw new Exception("Unable to save queue assignment");
		}
		return true;
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
	 * Returns the fields that have been defined for this Queue when a ticket is assigned to it.
	 *
	 * @return array
	 */
	protected function getAssignmentFieldDefinitions()
	{
		$flds = array();
		if ($ass_fields = \CJSON::decode($this->assignment_fields)) {
			foreach ($ass_fields as $ass_fld) {
				$flds[] = array(
						'id' => "{$ass_fld['id']}",
						'form_name' => self::$FIELD_PREFIX . $ass_fld['id'],
						'required' => $ass_fld['required'],
						'type' => @$ass_fld['type'],
						'label' => $ass_fld['label'],
						'choices' => @$ass_fld['choices']
				);
			}
		}
		return $flds;
	}

	/**
	 * Function to return a list of the fields that we are expecting an assignment form to contain for this queue
	 *
	 * @return array(array('id' => string, 'required' => boolean, 'choices' => array(), 'label' => string, 'type' => string))
	 */
	public function getFormFields()
	{
		$flds = array();

		// priority and notes are reserved fields and so get additional _ prefix for the field name
		if ($this->is_initial) {
			$flds[] = array(
				'id' => "_priority",
				'form_name' => self::$FIELD_PREFIX . "_priority",
				'required' => true,
				'choices' => \CHtml::listData(Priority::model()->findAll(), 'id', 'name'),
				'label' => 'Priority',
			);
		}
		$flds[] = array(
			'id' => "_notes",
			'form_name' => self::$FIELD_PREFIX . "_notes",
			'required' => false,
			'type' => 'textarea',
			'label' => 'Notes');

		return array_merge($flds, $this->getAssignmentFieldDefinitions());
	}
}

