// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript controller for the Modal Form of MOODLE.
 *
 * @module     block_catquiz_feedbackwizard
 * * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core_form/modalform', 'core/notification', 'core/str'],
    function(ModalForm, Notification, Str) {

    function openWizard(courseid, step, draftid) {
    step = step || 1;

    draftid = draftid || 0;

    var saveKey = step < 3 ? 'submitnext' : 'submitfinal';

    return Promise.all([
        Str.get_string('pluginname', 'block_catquiz_feedbackwizard'),
        Str.get_string(saveKey, 'block_catquiz_feedbackwizard'),
        Str.get_string('submitprevious', 'block_catquiz_feedbackwizard'),
    ]).then(function(results) {
        var title = results[0];

        var saveText = results[1];
        // var backText = results[2];

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

