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

namespace OEModule\PatientTicketing\widgets;

use OEModule\PatientTicketing\models;

class TicketAssignOutcome extends BaseTicketAssignment {

	const FOLLOWUP_Q_MIN = 1;
	const FOLLOWUP_Q_MAX = 12;
	public $hideFollowUp = true;

	public function run()
	{
		if (\Yii::app()->request->isPostRequest) {
			if ($_POST[$this->form_name]) {
				if ($outcome_id = @$_POST[$this->form_name]['outcome']) {
					$outcome = models\TicketAssignOutcomeOption::model()->findByPk((int)$outcome_id);
					if ($outcome->followup) {
						$this->hideFollowUp = false;
					}
				}
			}
		}

		parent::run();
	}

	public function getOutcomeOptions()
	{
		$res = array('options' => array());
		$models = models\TicketAssignOutcomeOption::model()->findAll();
		foreach ($models as $opt) {
			$res['options'][(string)$opt->id] = array('data-followup' => $opt->followup);
		}
		$res['list_data'] = \CHtml::listData($models, 'id', 'name');
		return $res;
	}

	/**
	 * Generates array list of follow up values
	 *
	 * @return array
	 */
	public function getFollowUpQuantityOptions()
	{
		$opts = array();
		for ($i = self::FOLLOWUP_Q_MIN; $i <= self::FOLLOWUP_Q_MAX; $i++) {
			$opts[(string) $i] = $i;
		}
		return $opts;
	}

	/**
	 * Extract form data for storing in assignment table
	 *
	 * @param $form_data
	 * @return array|void
	 */
	public function extractFormData($form_data)
	{
		$res = array();
		foreach (array('outcome', 'followup_quantity', 'followup_period', 'site') as $k) {
			$res[$k] = @$form_data[$k];
		}
		return $res;
	}

	/**
	 * Perform form data validation
	 *
	 * @param $form_data
	 * @return array
	 */
	public function validate($form_data)
	{
		$errs = array();
		if (!@$form_data['outcome']) {
			$errs['outcome'] = "Please select an outcome";
		}

		$outcome = models\TicketAssignOutcomeOption::model()->findByPk((int)$form_data['outcome']);
		if ($outcome->followup) {
			// validate outcome fields
			foreach (array(
				 'followup_quantity' => 'follow up quantity',
				 'followup_period' => 'follow up period',
				 'site' => 'site') as $k => $v) {
				if (!@$form_data[$k]) {
					$errs[$k] = "Please select {$v}";
				}
			}
		}

		return $errs;
	}

	/**
	 * Stringify the provided data structure for this widget
	 *
	 * @param $data
	 * @return string
	 */
	public function formatData($data)
	{
		$res = $data['outcome'];
		if (@$data['followup_quantity']) {
			$res .= " in " . $data['followup_quantity']  . " " . $data['followup_period'] . " at " . $data['site'];
		}

		return $res;
	}
}