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
<?php
$form = $this->beginWidget('BaseEventTypeCActiveForm', array(
				'id'=>'queueset-form',
				'enableAjaxValidation'=>false,
				'layoutColumns' => array(
						'label' => 3,
						'field' => 8
				)));
?>

<div class="row">
	<div id="current-users-col" class="column large-6">
		<h3>Current Users</h3>
		<ul id="current-users-list">
			<?php
			foreach ($queueset->permissioned_users as $user) {
				$this->renderPartial('form_queueset_perms_user', array(
							'fullname' => $user->getFullName(),
							'username' => $user->username,
							'id' => $user->id,
						));
			}
			?>
		</ul>
	</div>
	<div id="new-user-col" class="column large-6 end">
		<h3>Add User(s)</h3>
		<div class="autocomplete-row">
			<?php
			$this->widget('zii.widgets.jui.CJuiAutoComplete', array(
							'name' => "new_user",
							'id' => "new-user",
							'value'=>'',
							'source'=>"js:function(request, response) {
							console.log(request);
							$.ajax({
								'url': '" . Yii::app()->createUrl('/admin/userfind') . "',
								'type':'GET',
								'data':{'search': request.term},
								'success':function(data) {
									data = $.parseJSON(data);
									response(data);
								}
							});
						}",
							'options' => array(
								'minLength'=>'3',
								'select' => "js:function(event, ui) {
								console.log(ui);
								$('#current-users-list').append(Mustache.render($('#user-template').html(), {fullname: ui.item.value, username: ui.item.username, id: ui.item.id}));
								$('#new-user').val('');
								return false;
							}",
							),
							'htmlOptions' => array(
									'placeholder' => 'search by name or username'
							),
					));
			?>
		</div>
	</div>
	<script id="user-template" type="x-tmpl-mustache">
		<?php
			$this->renderPartial('form_queueset_perms_user', array(
				'fullname' => "{{fullname}}",
				'username' => "{{username}}",
				'id' => "{{id}}",
			));
		?>
	</script>
</div>

<?php
$this->endWidget();