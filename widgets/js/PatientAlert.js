/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

$(document).ready(function () {
	$(document).on('click', '#patient-alert-patientticketing .alert-box .toggle-trigger', function(e) {
		if ($(this).hasClass('toggle-show')) {
			target = "/PatientTicketing/default/collapseTicket";
		}
		else {
			target = "/PatientTicketing/default/expandTicket";
		}

		var getData = {ticket_id: $(this).parent().data('ticket-id')};

		$.ajax({
			url: target,
			data: getData,
			error: function() {
				e.preventDefault();
			}
		});
	});

	$(document).on('click', '.auto-save', function(e) {
		e.preventDefault();
		var href = $(this).attr('href');
		var patient = encodeURIComponent($('#patient-alert-patientticketing').data('patient-id'));
		var notes = encodeURIComponent($('#patientticketing__notes').val());
		$.ajax({
			url: "/PatientTicketing/default/autoSaveNotes/",
			data: 'notes='+notes+'&patient_id='+patient+'&YII_CSRF_TOKEN='+YII_CSRF_TOKEN,
			type: 'POST',
			dataType: 'json',
			success: function (response) {
				$(window).off('beforeunload');
				window.location.href = href;
			}.bind(this),
			error: function() {
				new OpenEyes.UI.Dialog.Alert({content: 'An error occurred'}).open();
			}.bind(this)
		})
	});
});
