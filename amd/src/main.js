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
 * JavaScript controller for the catquiz feedback wizard modal form.
 *
 * This module handles the initialization and management of the multi-step
 * feedback wizard modal form, including step navigation and form submission.
 *
 * @module     block_catquiz_feedbackwizard/main
 * @copyright  2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core_form/modalform', 'core/notification', 'core/str'],
    function(ModalForm, Notification, Str) {

        // Globale Variable für maximale Steps
        var maxSteps = 6; // Standard Value.

        /**
         * Opens the feedback wizard modal form.
         *
         * Creates and displays a modal form for the catquiz feedback wizard,
         * handling multi-step navigation and form submission responses.
         *
         * @param {number} courseid The course ID where the wizard is being used
         * @param {number} step The current step in the wizard (1-3)
         * @param {number} draftid The draft ID for saving progress between steps
         * @returns {Promise} Promise that resolves when the modal is shown
         */
        function openWizard(courseid, step, draftid) {
            step = step || 1;
            draftid = draftid || 0;

            var saveKey = step < maxSteps ? 'submitnext' : 'submitfinal';
            var cancelKey = step > 1 ? true : false;

            return Promise.all([
                Str.get_string('pluginname', 'block_catquiz_feedbackwizard'),
                Str.get_string(saveKey, 'block_catquiz_feedbackwizard'),
                Str.get_string('submitprevious', 'block_catquiz_feedbackwizard'),
            ]).then(function(results) {
                var title = results[0];
                var nextText = results[1];
                var backText = results[2];

                var modalForm = new ModalForm({
                    formClass: 'block_catquiz_feedbackwizard\\form\\wizard',
                    args: {
                        courseid: courseid,
                        step: step,
                        draftid: draftid},
                    modalConfig: {
                        title: title,
                        type: 'SAVE_CANCEL',
                        large: true,
                        scrollable: true,
                        showSaveButton: true,
                        showCancelButton: cancelKey},
                    saveButtonText: nextText
                });

                /**
                 * Closes the modal form using the appropriate API method.
                 *
                 * @returns {Promise} Promise that resolves when modal is closed
                 */
                var closeModal = function() {
                    if (typeof modalForm.close === 'function') {
                        return modalForm.close(); // Newer APIs
                    } else if (modalForm.modal && typeof modalForm.modal.destroy === 'function') {
                        modalForm.modal.destroy(); // Older Modal API
                        return Promise.resolve();
                    } else if (modalForm.modal && typeof modalForm.modal.hide === 'function') {
                        modalForm.modal.hide(); // Fallback
                        return Promise.resolve();
                    }
                    return Promise.resolve();
                };

                /**
                 * Handles the continue status response.
                 *
                 * @param {Object} response The response object from form submission
                 * @returns {Promise} Promise chain for continue handling
                 */
                var handleContinueStatus = function(response) {
                    // Notification.addNotification({message: response.message, type: 'success'});
                    return openWizard(courseid, response.nextstep, response.draftid);
                };

                /**
                 * Handles the submitted status response.
                 *
                 * @param {Object} response The response object from form submission
                 * @returns {void}
                 */
                var handleSubmittedStatus = function(response) {
                    // Notification.addNotification({message: response.message, type: 'success'});
                };

                // Handle form submission events.
                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, function(e) {
                    var response = e.detail || {};

                    if (response.status === 'continue') {
                        closeModal()
                            .then(function() {
                                return handleContinueStatus(response);
                            })
                            .catch(Notification.exception);

                    } else if (response.status === 'submitted') {
                        closeModal()
                            .then(function() {
                                handleSubmittedStatus(response);
                            })
                            .catch(Notification.exception);
                    }
                });

                // Handle form cancellation events.
                modalForm.addEventListener(modalForm.events.FORM_CANCELLED, function() {
                    closeModal().catch(Notification.exception);
                });

                modalForm.show().then(function() {
                    // Manipulate Cancel-Button after showing up.
                    var cancelBtn = document.querySelector('.modal-footer .btn-secondary');
                    if (cancelBtn) {
                        if (cancelKey) {
                            cancelBtn.textContent = backText;
                        } else {
                            cancelBtn.style.display = 'none';
                        }
                    }
                });

            }).catch(Notification.exception);
        }

        return {
            /**
             * Initialize the feedback wizard functionality.
             *
             * Sets up event listeners for wizard trigger buttons and handles
             * the opening of the wizard modal when triggered.
             *
             * @returns {void}
             */
            init: function(params) {
                // Read in parameters.
                if (params && params.length > 0 && params[0].maxSteps) {
                    maxSteps = parseInt(params[0].maxSteps, 10);
                }

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
