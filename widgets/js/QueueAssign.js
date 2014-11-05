$(document).on('click', '.auto-save', function(e) {
	e.preventDefault();
	var href = $(this).attr('href');
	var queue = $(this).data('queue');
	var patient = encodeURIComponent($('#patient-alert-patientticketing').data('patient-id'));
	var form = $(this).closest('.PatientTicketing-moveTicket').serialize();

	$.ajax({
		url: "/PatientTicketing/default/autoSave/",
		data: form+'&patient_id='+patient,
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