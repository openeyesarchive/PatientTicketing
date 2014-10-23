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
if ($queue) {?>
	<div class="row">
	<div class="large-6 column">
		<?php foreach ($queue->getFormFields() as $fld) {?>
			<fieldset class="field-row row">
				<div class="large-<?= $this->label_width ?> column">
					<label for="<?= $fld['form_name']?>"><?= $fld['label'] ?>:</label>
				</div>
				<div class="large-<?= $this->data_width ?> column end">
					<?php if (@$fld['choices']) {
						echo CHtml::dropDownList(
								$fld['form_name'],
								@$_POST[$fld['form_name']],
								$fld['choices'],
								array('empty' => ($fld['required']) ? ' - Please Select - ' : 'None'));
					} else {
						//may need to expand this beyond textarea and select in the future.
						if($_POST) {
							$notes = @$_POST[$fld['form_name']];
						}
						else {
							$notes = @Yii::app()->session['pt_notes_'.$this->patient_id.'_'.$current_queue_id];
						}
						?>
						<textarea id="<?= $fld['form_name']?>" name="<?= $fld['form_name']?>"><?=$notes?></textarea>
						<?php
						if(isset(Yii::app()->session['pt_notes_'.$this->patient_id.'_'.$current_queue_id]))
						{
							?>
							<script>
								$(document).ready(function(){
								window.patientTicketChanged = true;
								window.changedTickets[<?=$current_queue_id?>]=true;
								});
							</script>
						<?php
						}
						?>
					<?php }?>
				</div>
			</fieldset>
		<?php }?>
	</div>
	<div class="large-6 column end">
		<?php
		if ($this->patient_id) { ?>
			<ul>
			<?php
			foreach ($queue->event_types as $et) {
				?>
				<li><a href="<?= Yii::app()->baseURL?>/<?=$et->class_name?>/default/create?patient_id=<?= $this->patient_id ?>" class="button small event-type-link auto-save" data-queue="<?= $current_queue_id?>"><?= $et->name ?></a></li>
			<?php
			}
			?>
			</ul>

		<?php
		}
		?>
	</div>
	</div>
<?php } ?>
<?php

?>