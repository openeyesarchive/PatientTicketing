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
<?php if (count($tickets) && Yii::app()->user->checkAccess('OprnViewClinical')) { ?>
	<div class="row">
		<div class="large-12 column">
			<?php foreach ($tickets as $ticket) {
				$cat = $t_svc->getCategoryForTicket($ticket);
			?>
			<div class="alert-box issue js-toggle-container">
						<span class="box-title"><?= $cat->name ?>: Patient is in <?= $ticket->current_queue->queueset->name ?>, <?= $ticket->current_queue->name ?></span>
						<a href="#" class="toggle-trigger toggle-show js-toggle">
							<span class="icon-showhide">
								Show/hide this section
							</span>
						</a>
					<div class="js-toggle-body" style="display: none;">
						<?php $this->widget($summary_widget, array('ticket' => $ticket)); ?>
						<!-- Patient is in  - <a href="<?= Yii::app()->createURL("//PatientTicketing/default/", array('cat_id' => $cat->id, 'patient_id' => $this->patient->id)) ?>"><?= $ticket->current_queue->name ?> </a> -->
					</div>
			</div>
			<?php }?>
		</div>
	</div>
<?php } ?>