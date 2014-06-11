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

/**
 * function to display pop up for moving a ticket to a new queue
 * @param id
 * @param outcomes
 */

(function() {
	/**
	 * TicketController provides functions to control the Ticket Viewer page.
	 *
	 * @param options
	 * @constructor
	 */
	function TicketController(options) {
		this.options = $.extend(true, {}, TicketController._defaultOptions, options);
		this.queueAssForms = {};
		this.currentTicketId = null;

		/**
		 * Appends the given html to the ticket table row for the given id.
		 *
		 * @param id
		 * @param html
		 */
		this.appendHistory = function(id, html)
		{
			var historyRows = $(this.getTicketRowSelector(id)).filter(this.options.ticketHistoryFilter);
			if ($(historyRows).length) {
				historyRows.remove();
			}
			$(this.getTicketRowSelector(id)).after(html);
		}
	}

	TicketController._defaultOptions = {
		queueAssignmentFormURI: "/PatientTicketing/default/getQueueAssignmentForm/",
		ticketMoveURI: "/PatientTicketing/default/moveTicket/",
		ticketRowSelectorPrefix: "tr[data-ticket-id='",
		ticketRowSelectorPostfix: "']",
		getTicketRowURI: "/PatientTicketing/default/getTicketTableRow/",
		queueTemplateSelector: '#ticketcontroller-queue-select-template',
		queueAssignmentPlaceholderSelector: '#queue-assignment-placeholder',
		ticketHistoryFilter: ".history",
		getTicketHistoryURI: "/PatientTicketing/default/getTicketTableRowHistory/",
		takeTicketURI: "/PatientTicketing/default/takeTicket/",
		releaseTicketURI: "/PatientTicketing/default/releaseTicket/"
	};

	/**
	 * Convenience function to construct the table row selector for the given ticket id
	 *
	 * @param id
	 * @returns {string}
	 */
	TicketController.prototype.getTicketRowSelector = function(id)
	{
		if (!id) {
			id = this.currentTicketId;
		}
		return this.options.ticketRowSelectorPrefix+id+this.options.ticketRowSelectorPostfix;
	}
	/**
	 * Creates the popup for the given Ticket definition to move it to a new Queue (based on the given outcomes)
	 *
	 * @param ticketInfo
	 * @param outcomes
	 */
	TicketController.prototype.moveTicket = function(ticketInfo, outcomes)
	{
		var template = $(this.options.queueTemplateSelector).html();

		if (!template) {
			throw new Error('Unable to compile queue selector template. Template not found: ' + this.options.queueTemplateSelector);
		}

		this.currentTicketId = ticketInfo.id;
		var templateVals = $.extend(true, {}, ticketInfo, {CSRF_TOKEN: YII_CSRF_TOKEN});
		templateVals.outcome_options = '';
		for (var i = 0; i < outcomes.length; i++) {
			templateVals.outcome_options += '<option value="'+outcomes[i].id+'">'+outcomes[i].name+'</option>';
		}

		this.dialog = new OpenEyes.UI.Dialog.Confirm({
			content: Mustache.render(template, templateVals)
		});
		this.dialog.open();
		this.dialog.on('ok', function() {this.submitTicketMove()}.bind(this));
		this.dialog.on('cancel', function() {this.cancelTicketMove().bind(this)});
	}

	TicketController.prototype.cancelTicketMove = function()
	{
		this.currentTicketId = null;
	}
	/**
	 * process the Ticket Move form
	 */
	TicketController.prototype.submitTicketMove = function()
	{
		// do some basic form validation here, and either display an error or begin the ajax request.
		// should disable the table row for the ticket being updated
		var rowSelector = this.getTicketRowSelector();
		disableButtons(rowSelector);
		$(rowSelector).addClass('disabled');
		$.ajax({
			url: this.options.ticketMoveURI + this.currentTicketId,
			data: $(this.dialog.content).find('form').serialize(),
			type: 'POST',
			success: function(response) {this.reloadTicket(this.currentTicketId); this.currentTicketId = null;}.bind(this),
			error: function(jqXHR, status, error) {
				enableButtons(rowSelector);
			}.bind(this)
		})
		// then reload the table row for that ticket so it is refreshed.
	}

	/**
	 * Reload the row for the current ticket (called after a ticket is updated)
	 */
	TicketController.prototype.reloadTicket = function(id)
	{
		$.ajax({
			url: this.options.getTicketRowURI + id,
			success: function(response) {
				if ($(this.getTicketRowSelector(id)).filter(this.options.ticketHistoryFilter).length) {
					$(this.getTicketRowSelector(id)).filter(this.options.ticketHistoryFilter).slideUp().remove();
				}
				$(this.getTicketRowSelector(id)).slideUp().replaceWith(response).slideDown();
			}.bind(this)
		});
	}

	/**
	 * Retrieves the assignment form for the given queue id (caching for future use)
	 *
	 * @param integer id
	 * @param callback success
	 */
	TicketController.prototype.getQueueAssForm = function(id, success)
	{
		if (!this.queueAssForms[id]) {
			disableButtons();
			var self = this;
			var form  = $.ajax({
				url: this.options.queueAssignmentFormURI,
				data: {id: id},
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
	TicketController.prototype.setQueueAssForm = function(id)
	{
		if (id) {
			this.getQueueAssForm(id, function onSuccess(form) {
				$(this.dialog.content.html).find(this.options.queueAssignmentPlaceholderSelector).html(form)
			}.bind(this));
		}
		else {
			$(this.dialog.content.html).find(this.options.queueAssignmentPlaceholderSelector).html('');
		}
	}

	/**
	 * Toggles the history rows for the given ticket.
	 *
	 * @param ticketInfo
	 */
	TicketController.prototype.toggleHistory = function(ticketInfo)
	{
		var historyRows = $(this.getTicketRowSelector(ticketInfo.id)).filter(this.options.ticketHistoryFilter);
		if (historyRows.length) {
			if (historyRows.is(":visible")) {
				historyRows.slideUp();
			}
			else {
				historyRows.slideDown();
			}
		}
		else {
			$.ajax({
				url: this.options.getTicketHistoryURI + ticketInfo.id,
				success: function(response) {
					this.appendHistory(ticketInfo.id, response);
				}.bind(this)
			});
		}
	}

	/**
	 * Have the currently logged in user take control of the given ticket and refreshes the table row for it.
	 *
	 * @param ticketInfo
	 */
	TicketController.prototype.takeTicket = function(ticketInfo)
	{
		$.ajax({
			url: this.options.takeTicketURI + ticketInfo.id,
			success: function(response) {
				if (response.message) {
					OpenEyes.Util.Alert(response.message).open();
				}
				this.reloadTicket(ticketInfo.id);
			}.bind(this)
		});
	}

	/**
	 * Release the ticket for the currently logged in user and refreshes the table row for it.
	 *
	 * @param ticketInfo
	 */
	TicketController.prototype.releaseTicket = function(ticketInfo)
	{
		$.ajax({
			url: this.options.releaseTicketURI + ticketInfo.id,
			success: function(response) {
				if (response.message) {
					OpenEyes.Util.Alert(response.message).open();
				}
				this.reloadTicket(ticketInfo.id);
			}.bind(this)
		});
	}

	$(document).ready(function() {
		var ticketController = new TicketController();

		$(this).on('click', '.ticket-take', function(e) {
			var ticketInfo = $(this).closest('tr').data('ticket-info');
			ticketController.takeTicket(ticketInfo);
		});

		$(this).on('click', '.ticket-release', function(e) {
			var ticketInfo = $(this).closest('tr').data('ticket-info');
			ticketController.releaseTicket(ticketInfo);
		});

		$(this).on('click', '.ticket-move', function(e) {
			var ticketInfo = $(this).closest('tr').data('ticket-info');
			var outcomes = $(this).data('outcomes');
			ticketController.moveTicket(ticketInfo, outcomes);
		});

		$(this).on('change', '#to_queue_id', function(e) {
			ticketController.setQueueAssForm($(e.srcElement).val());
		});

		$(this).on('click', '.ticket-history', function(e) {
			var ticketInfo = $(this).closest('tr').data('ticket-info');
			ticketController.toggleHistory(ticketInfo);
		});

	});
}());