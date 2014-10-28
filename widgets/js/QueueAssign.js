$(document).on('click', '.auto-save', function(e) {
	e.preventDefault();
	var href = $(this).attr('href');
	var queue = $(this).data('queue');
	var patient = encodeURIComponent($('#patient-alert-patientticketing').data('patient-id'));
	var notes = encodeURIComponent($(this).closest('.row').find('#patientticketing__notes').val());

	$.ajax({
		url: "/PatientTicketing/default/autoSaveNotes/",
		data: 'notes='+notes+'&patient_id='+patient+'&queue='+queue+'&YII_CSRF_TOKEN='+YII_CSRF_TOKEN,
		type: 'POST',
		dataType: 'json',
		success: function (response) {
			if(Object.keys(window.changedTickets).length < 2 && !window.formHasChanged) {
				if(window.changedTickets[queue]==true){
					$(window).off('beforeunload');
				}
			}
			window.location.href = href;
		}.bind(this),
		error: function() {
			new OpenEyes.UI.Dialog.Alert({content: 'An error occurred'}).open();
		}.bind(this)
	})
});