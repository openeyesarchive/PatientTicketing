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

<tr data-ticket-id="<?= $ticket->id?>" data-ticket-info="<?= CHtml::encode($ticket->getInfoData()) ?>">
	<td><?= $ticket->currentQueue->name ?></td>
	<td><?= $ticket->patient->hos_num ?></td>
	<td><?= $ticket->patient->first_name ?></td>
	<td><?= $ticket->patient->last_name ?></td>
	<td><?= $ticket->patient->age ?></td>
	<td style="color: <?= $ticket->priority->colour ?>"><?= $ticket->priority->name ?></td>
	<td><a href="<?= $ticket->getSourceLink() ?>"><?= $ticket->getSourceLabel()?></a></td>
	<td><?= Helper::convertDate2NHS($ticket->created_date)?></td>
	<td><?= $ticket->getTicketFirm() ?></td>
	<td><?= $ticket->report ? $ticket->report : "-"; ?></td>
	<td><?= Yii::app()->format->Ntext($ticket->getNotes()) ?></td>
	<td><?= $ticket->assignee ? $ticket->assignee->getFullName() : "-"?></td>
	<td nowrap>
		<?php
		if ($this->checkAccess('oprnEditPatientTicket')) {
			if (!$ticket->is_complete()) {
				if ($ticket->assignee) {
					if ($ticket->assignee_user_id == Yii::app()->user->id) {
						?><button id="release" class="tiny ticket-release">Release</button><?php
					}
				}
				else {
					?><button id="take" class="tiny ticket-take">Take</button><?php
				}?>
				<button class="tiny ticket-move" data-outcomes="<?= CHtml::encode($ticket->currentQueue->getOutcomeData()) ?>">Move</button>
		<?php }
		} ?>
		<?php if ($ticket->hasHistory()) {?>
			<button class="tiny ticket-history">History</button>
		<?php } ?>
	</td>
</tr>
