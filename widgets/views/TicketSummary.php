<div class="panel">
	<div class="row data-row">
		<div class="large-4 column left">
			<div class="data-label"><?= $ticket->getDisplayQueue()->name  . " (" . Helper::convertDate2NHS($ticket->getDisplayQueueAssignment()->assignment_date) . ")" ?></div>
		</div>
		<div class="large-6 column left">
			<div class="data-value"><?= Yii::app()->format->Ntext($ticket->getDisplayQueueAssignment()->notes)?></div>
		</div>
	</div>
	<div class="row data-row">
		<?php if ($ticket->priority) {?>
		<div class="large-1 column">
			<div class="data-label">Priority:</div>
		</div>
		<div class="large-1 column left">
			<div class="data-value" style="color: <?= $ticket->priority->colour?>">
				<?= $ticket->priority->name ?>
			</div>
		</div>
		<?php }?>
	</div>
	<?php if ($ticket->report) {?>
		<div class="row data-row">
			<div class="large-1 column">
				<div class="data-label">Info:</div>
			</div>
			<div class="large-4 column left">
				<div class="data-value"><?= $ticket->report; ?></div>
			</div>
		</div>
	<?php }
	if ($ticket->hasHistory()) {?>
		<hr />
		<?php foreach ($ticket->queue_assignments as $old_ass)	{?>
			<div class="row data-row<?php if ($old_ass->id == $ticket->getDisplayQueueAssignment()->id) {?> current_queue<?php }?>" style="font-style: italic;">
				<div class="large-2 column">
					<div class="data-label"><?= $old_ass->queue->name ?>:</div>
				</div>
				<div class="large-2 column left">
					<div clas="data-value"><?= Helper::convertDate2NHS($old_ass->assignment_date)?></div>
				</div>
				<?php if ($old_ass->notes) {?>
					<div class="large-6 column left">
						<div class="data-value"><?= Yii::app()->format->Ntext($old_ass->notes) ?></div>
					</div>
				<?php }?>
			</div>
		<?php }
	}?>
</div>
