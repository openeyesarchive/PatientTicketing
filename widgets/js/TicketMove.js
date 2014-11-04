
(function() {
	function TicketMoveController(options) {
		this.options = $.extend(true, {}, TicketMoveController._defaultOptions, options);
		this.queueAssForms = {};
		this.patientId = $(this.options.patientAlertSelector).data('patient-id');
	}

	TicketMoveController._defaultOptions = {
		queueAssignmentFormURI: "/PatientTicketing/default/getQueueAssignmentForm/",
		reloadPatientAlertURI: "/PatientTicketing/default/getPatientAlert",
		formSelector: "#PatientTicketing-moveForm",
		formClass: '.PatientTicketing-moveTicket',
		queueAssignmentPlaceholderSelector: "#PatientTicketing-queue-assignment",
		ticketMoveURI: "/PatientTicketing/default/moveTicket/",
		patientAlertSelector: "#patient-alert-patientticketing"
	}

	/**
	 * Retrieves the assignment form for the given queue id (caching for future use)
	 *
	 * @param integer id
	 * @param callback success
	 */
	TicketMoveController.prototype.getQueueAssForm = function(id, success)
	{
		if (!this.queueAssForms[id]) {
			disableButtons();
			var self = this;
			var form	= $.ajax({
				url: this.options.queueAssignmentFormURI,
				data: {id: id, ticket_id: this.ticketId},
				success: function(response) {
					self.queueAssForms[id] = response;
					enableButtons();
					success(response);
				},
				error: function(jqXHR, status, error) {
					enableButtons();
					throw new Error("Unable to retrieve assignment form for queue with id " + id + ": " + error);
				}
			});
		}
		else {
			success(this.queueAssForms[id]);
		}
	}

	/**
	 * Sets the Queue Assignment form in the Ticket Move popup.
	 *
	 * @param integer id
	 */
	TicketMoveController.prototype.setQueueAssForm = function(form, id)
	{
		if (id) {
			this.getQueueAssForm(id, function onSuccess(assForm) {
				form.find(this.options.queueAssignmentPlaceholderSelector).html(assForm)
			}.bind(this));
		}
		else {
			form.find(this.options.queueAssignmentPlaceholderSelector).html('');
		}
	}

	/**
	 * Reload the patient alert banner
	 */
	TicketMoveController.prototype.reloadPatientAlert = function()
	{
		$.ajax({
			url: this.options.reloadPatientAlertURI,
			data: {patient_id: this.patientId},
			success: function(response) {
				$(this.options.patientAlertSelector).replaceWith(response)
			}.bind(this),
			error: function(jqXHR, status, error) {
				new OpenEyes.UI.Dialog.Alert({content: 'An unexpected error occurred.'}).open();
			}.bind(this)
		});
	};

	/**
	 * process the Ticket Move form
	 */
	TicketMoveController.prototype.submitForm = function(form)
	{
		disableButtons(this.options.formSelector);
		var errors = form.find('.alert-box');

		if (!form.find('[name=to_queue_id]').val()) {
			errors.text('Please select a destination queue').show()
			return;
		}

		var ticket_id = form.find('input[name="ticket_id"]').val();

		errors.hide();
		$.ajax({
			url: this.options.ticketMoveURI + ticket_id,
			data: form.serialize(),
			type: 'POST',
			dataType: 'json',

			success: function (response) {
				if (response.errors) {
					errors.text('');
					for (var i in response.errors) errors.append(response.errors[i] + "<br>");
					errors.show();
				} else {
					//this.reloadPatientAlert();
					if(response.redirectURL){
					window.location = response.redirectURL;
					}
				}
			}.bind(this),
			error: function(jqXHR, status, error) {
				this.reloadPatientAlert();
				new OpenEyes.UI.Dialog.Alert({content: 'Could not move ticket'}).open();
			}.bind(this),
			complete: function() {
				enableButtons(this.options.formSelector);
			}.bind(this)
		})
	}

	$(document).ready(function() {
		var ticketMoveController = new TicketMoveController();

		$(document).on('click', ticketMoveController.options.formClass + ' .ok', function(e) {
			e.preventDefault();
			clearAutoSave($(this).data('queue'));
			ticketMoveController.submitForm($(this).parents('form'));
		});

		$(document).on('click', ticketMoveController.options.formClass + ' .cancel', function(e) {
			var queueset_id = $(this).data('queue');
			var category_id = $(this).data('category');
			delete(window.changedTickets[queueset_id]);
			if(Object.keys(window.changedTickets).length==0) window.patientTicketChanged = false;
			$(this).closest('.PatientTicketing-moveTicket').find('#patientticketing__notes').val("");
			//$(this).parents('.alert-box').find('.js-toggle').trigger('click');
			window.location = "/PatientTicketing/default/?queueset_id=" + queueset_id + "&cat_id=" + category_id;
		});

	  function clearAutoSave(queue)
		{
			var patient = encodeURIComponent($('#patient-alert-patientticketing').data('patient-id'));
			$.ajax({
				url: "/PatientTicketing/default/autoSaveNotes/",
				data: 'notes=&patient_id='+patient+'&queue='+queue+'&YII_CSRF_TOKEN='+YII_CSRF_TOKEN,
				type: 'POST',
				dataType: 'json',
				success: function (response) {
					delete window.changedTickets[queue];
					if(Object.keys(window.changedTickets).length==0) 	window.patientTicketChanged = false;
				}.bind(this),
				error: function() {
					new OpenEyes.UI.Dialog.Alert({content: 'An error occurred'}).open();
				}.bind(this)
			});
		}
		$(document).on('change', ticketMoveController.options.formClass + ' select[name="to_queue_id"]', function(e) {
			ticketMoveController.setQueueAssForm($(this).parents('form'), $(e.target).val());
		});


	});

})();