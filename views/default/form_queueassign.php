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
?>

<div>
	<fieldset class="field-row row">
		<?php if ($queue->is_initial) {?>
			<div class="large-<?= $label_width ?> column">
				<label for="patientticketing__priority">Priority:</label>
			</div>
			<div class="large-<?= $field_width ?> column end">
				<?php echo CHtml::dropDownList('patientticketing__priority',null, CHtml::listData(OEModule\PatientTicketing\models\Priority::model()->findAll(), 'id', 'name'), array('empty' => ' - Please Select - ')) ?>
			</div>
		<?php } ?>
	</fieldset>
	<fieldset class="field-row row">
		<div class="large-<?= $label_width ?> column">
			<label for="queue-ass-notes">Notes:</label>
		</div>
		<div class="large-<?= $field_width ?> column end">
			<textarea id="queue-ass-notes" name="patientticketing__notes"></textarea>
		</div>
		<!-- TODO: implement the dynamic additional data fields for queue assignment -->
	</fieldset>
</div>