define(['core_form/modalform', 'core/notification', 'core/str'], function(ModalForm, Notification, Str) {
    function openWizard(courseid, step, draftid) {
    step = step || 1;
    draftid = draftid || 0;
    var saveKey = step < 3 ? 'submitnext' : 'submitfinal';
    return Promise.all([
        Str.get_string('pluginname', 'block_catquiz_feedbackwizard'),
        Str.get_string(saveKey, 'block_catquiz_feedbackwizard')
    ]).then(function(results) {
        var title = results[0];
        var saveText = results;
        var modalForm = new ModalForm({
            formClass: 'block_catquiz_feedbackwizard\\form\\wizard',
            args: {courseid: courseid, step: step, draftid: draftid},
            modalConfig: {title: title},
            saveButtonText: saveText
        });
        var closeModal = function() {
            if (typeof modalForm.close === 'function') {
                return modalForm.close(); // Neue(re) APIs
            } else if (modalForm.modal && typeof modalForm.modal.destroy === 'function') {
                modalForm.modal.destroy(); // Ältere Modal-API
                return Promise.resolve();
            } else if (modalForm.modal && typeof modalForm.modal.hide === 'function') {
                modalForm.modal.hide(); // Fallback
                return Promise.resolve();
            }
            return Promise.resolve();
        };
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, function(e) {
            var response = e.detail || {};
            if (response.status === 'continue') {
                closeModal().then(function() {
                    Notification.addNotification({message: response.message, type: 'success'});
                    openWizard(courseid, response.nextstep, response.draftid).catch(Notification.exception);
                });
            } else if (response.status === 'submitted') {
                closeModal().then(function() {
                    Notification.addNotification({message: response.message, type: 'success'});
                });
            }
        });
        modalForm.addEventListener(modalForm.events.FORM_CANCELLED, function() {
            closeModal();
        });
        modalForm.show();
    }).catch(Notification.exception);
}
return {
    init: function() {
        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('.js-open-catquiz_feedbackwizard[data-action="open-wizard"]');
            if (!trigger) {
                return;
            }
            e.preventDefault();
            var courseid = parseInt(trigger.getAttribute('data-courseid'), 10) || 0;
            openWizard(courseid, 1, 0).catch(Notification.exception);
        });
    }
};
});

