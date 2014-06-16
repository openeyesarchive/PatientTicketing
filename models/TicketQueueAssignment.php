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


class TicketQueueAssignment extends \BaseActiveRecordVersioned
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
		return 'patientticketing_ticketqueue_assignment';
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
				'assignment_user' => array(self::BELONGS_TO, 'User', 'assignment_user_id'),
				'assignment_firm' => array(self::BELONGS_TO, 'Firm', 'assignment_firm_id'),
				'ticket' => array(self::BELONGS_TO, 'OEModule\PatientTicketing\models\Ticket', 'ticket_id'),
				'queue' => array(self::BELONGS_TO, 'OEModule\PatientTicketing\models\Queue', 'queue_id'),
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
	 * Searches for string patterns to replace with assignment data and returns the resultant string.
	 *
	 * @param string $text
	 * @return string $replaced_text
	 */
	public function replaceAssignmentCodes($text)
	{
		if ($this->details) {
			$flds = json_decode($this->details, false);
			$by_id = array();
			foreach ($flds as $fld) {
				$by_id[$fld->id] = $fld->value;
			}
			// match for ticketing fields
			preg_match_all('/\[pt_([a-z]+)\]/is',$text,$m);

			foreach ($m[1] as $el) {
				$text = preg_replace('/\[pt_' . $el . '\]/is', @$by_id[$el] ? $by_id[$el] : 'Unknown', $text);
			}

			return $text;
		}
	}
}