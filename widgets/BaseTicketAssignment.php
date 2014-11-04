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


class BaseTicketAssignment extends \CWidget {

	public $shortName;
	public $ticket;
	public $label_width = 2;
	public $data_width = 4;
	public $form_name;
	public $assetFolder;

	public function init()
	{
		// if the widget has javascript, load it in
		$cls_name = explode('\\', get_class($this));
		$this->shortName = array_pop($cls_name);
		$path = dirname(__FILE__);
		if (file_exists($path . "/js/".$this->shortName.".js")) {
			$assetManager = \Yii::app()->getAssetManager();
			$this->assetFolder = $assetManager->publish($path . "/js/");
			$assetManager->registerScriptFile("js/".$this->shortName.".js", "application.modules.PatientTicketing.widgets");
		}
		parent::init();
	}

	public function extractFormData($form_data)
	{
		// should be implemented in the child class
	}

	public function valdiate($form_data)
	{
		// should be implemented in the child class
	}

	public function run()
	{
		$this->render($this->shortName);
	}
}