

(function () {
	function QueueAdmin(options) {
		this.options = $.extend(true, {}, QueueAdmin._defaultOptions, options);
		this.init();
		this.currentDisplayedQueueId = null;
	}

	QueueAdmin._defaultOptions = {
		'setSelector': '.queue-set',
		'parentSelector': '.initial-queue',
		'childSelector': '.child-queue',
		'addQueueURI': '/PatientTicketing/admin/addQueue',
		'editQueueURI': '/PatientTicketing/admin/updateQueue',
		'loadQueueURI': '/PatientTicketing/admin/loadQueueAsList',
		'deactivateQueueURI': '/PatientTicketing/admin/deactivateQueue',
		'activateQueueURI': '/PatientTicketing/admin/activateQueue'
	};

	QueueAdmin.prototype.init = function() {
	}

	QueueAdmin.prototype.displayQueue = function(queueId) {
		var selector = '#queue-container-' + queueId;
		$('#chart').html('');
		$(selector).jOrgChart({
			chartElement : '#chart',
			dragAndDrop: false,
			showSelector: '.show-children',
			hideSelector: '.hide-children',
			expansionSelector: '.expansion-controls'
		});
		this.currentDisplayedQueueId = queueId;
	}

	QueueAdmin.prototype.reloadQueue = function() {
		$('#chart').html('');
		$.ajax({
			url: this.options.loadQueueURI,
			data: {id: this.currentDisplayedQueueId},
			dataType: "json",
			success: function(resp) {
				$('#queue-container-'+resp.rootid).html(resp.list);
				this.displayQueue(resp.rootid)
			}.bind(this),
			error: function(jqXHR, status, error) {
				new OpenEyes.UI.Dialog.Alert({content: 'There was a problem reloading the queue data'}).open();
			}
		});
	}

	QueueAdmin.prototype.addQueue = function(queueId) {
		$.ajax({
				url: this.options.addQueueURI,
				data: {parent_id: queueId},
				success: function(content) {
					var formDialog = new OpenEyes.UI.Dialog.Confirm({
						title: "Add Queue",
						content: content,
						okButton: 'Save'
					});
					formDialog.open();
					// suppress default ok behaviour
					formDialog.content.off('click', '.ok');
					// manage form submission and response
					formDialog.content.on('click', '.ok', function() {this.submitQueueForm(formDialog, this.options.addQueueURI)}.bind(this));
				}.bind(this)
		});
	}

	QueueAdmin.prototype.editQueue = function(queueId) {
		$.ajax({
			url: this.options.editQueueURI,
			data: {id: queueId},
			success: function(content) {
				var formDialog = new OpenEyes.UI.Dialog.Confirm({
					title: "Edit Queue",
					content: content,
					okButton: 'Save'
				});
				formDialog.open();
				// suppress default ok behaviour
				formDialog.content.off('click', '.ok');
				// manage form submission and response
				formDialog.content.on('click', '.ok', function() {this.submitQueueForm(formDialog, this.options.editQueueURI + '?id=' + queueId)}.bind(this));
			}.bind(this)
		});
	}

	QueueAdmin.prototype.submitQueueForm = function(formDialog, submitURI) {
		$.ajax({
			url: submitURI,
			data: formDialog.content.find('form').serialize(),
			type: 'POST',
			dataType: 'json',
			success: function(resp) {
				if (resp.success) {
					formDialog.close();
					this.reloadQueue();
				}
				else {
					formDialog.setContent(resp.form);
				}
			}.bind(this),
			error: function(jqXHR, status, error) {
				formDialog.close();
				new OpenEyes.UI.Dialog.Alert({content: 'There was a problem saving the queue'}).open();
			}
		});
	}

	QueueAdmin.prototype.activeToggleQueue = function(queueId, active) {
		$.ajax({
			url: active ? this.options.deactivateQueueURI : this.options.activateQueueURI,
			data: {id: queueId},
			dataType: 'json',
			success: function(resp) {
				this.reloadQueue();
			}.bind(this),
			error: function(jqXHR, status, error) {
				formDialog.close();
				new OpenEyes.UI.Dialog.Alert({content: 'There was a problem changing queue state'}).open();
			}
		})
	}

	$(document).ready(function() {
		var queueAdmin = new QueueAdmin();

		$('.queue-link').on('click', function() {
			queueAdmin.displayQueue($(this).data('queue-id'));
		});

		$(this).on('click','.add-child', function(e) {
			var queueId = $(this).parent('div').data('queue-id');
			queueAdmin.addQueue(queueId);
		});

		$(this).on('click', '.edit', function() {
			var queueId = $(this).parent('div').data('queue-id');
			queueAdmin.editQueue(queueId);
		});

		$(this).on('click', '.active-toggle', function() {
			var queueId = $(this).parent('div').data('queue-id');
			var active = !$(this).closest('div.node').hasClass('inactive');
			queueAdmin.activeToggleQueue(queueId, active);
		});

	});
}());

