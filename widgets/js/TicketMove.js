
(function() {
	function TicketMoveController(options) {
		this.options = $.extend(true, {}, TicketMoveController._defaultOptions, options);
		this.queueAssForms = {};
		this.ticketId = $(this.options.formSelector).find("input[name='ticket_id']").val();
		this.patientId = $(this.options.formSelector).data('patient-id');
	}

	TicketMoveController._defaultOptions = {
		queueAssignmentFormURI: "/PatientTicketing/default/getQueueAssignmentForm/",
		reloadPatientAlertURI: "/PatientTicketing/default/getPatientAlert",
		formSelector: "#PatientTicketing-moveForm",
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
	TicketMoveController.prototype.setQueueAssForm = function(id)
	{
		if (id) {
			this.getQueueAssForm(id, function onSuccess(form) {
				$(this.options.formSelector).find(this.options.queueAssignmentPlaceholderSelector).html(form)
			}.bind(this));
		}
		else {
			$(this.dialog.content.html).find(this.options.queueAssignmentPlaceholderSelector).html('');
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
	TicketMoveController.prototype.submitForm = function()
	{
		var form = $(this.options.formSelector);
		disableButtons(this.options.formSelector);
		var errors = form.find('.alert-box');

		if (!form.find('[name=to_queue_id]').val()) {
			errors.text('Please select a destination queue').show()
			return;
		}

		errors.hide();
		$.ajax({
			url: this.options.ticketMoveURI + this.ticketId,
			data: form.serialize(),
			type: 'POST',
			dataType: 'json',
			success: function (response) {
				if (response.errors) {
					errors.text('');
					for (var i in response.errors) errors.append(response.errors[i] + "<br>");
					errors.show();
				} else {
					this.reloadPatientAlert();
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

		$(this).on('change', '#to_queue_id', function(e) {
			ticketMoveController.setQueueAssForm($(e.target).val());
		});
		$(this).on('click', ticketMoveController.options.formSelector + " .ok", function(e) {
			e.preventDefault();
			ticketMoveController.submitForm();
		})
	});

})();