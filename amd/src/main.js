/**
 * CAT Quiz wizard modal controller.
 *
 * @module     block_catquiz_feedbackwizard/main
 * @copyright  2026 OpenAI
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Build the AMD module.
 *
 * @param {Function} ModalForm Moodle modal form constructor.
 * @param {Object} Notification Moodle notification helper.
 * @param {Object} Str Moodle string helper.
 * @returns {Object}
 */
var wizardModule = function(ModalForm, Notification, Str) {
    var maxSteps = 6;

    /**
     * Close the modal form if possible.
     *
     * @param {ModalForm} modalForm The modal form instance.
     * @returns {Promise}
     */
    var closeModal = function(modalForm) {
        if (typeof modalForm.close === 'function') {
            return modalForm.close();
        }

        if (modalForm.modal && typeof modalForm.modal.destroy === 'function') {
            modalForm.modal.destroy();
            return Promise.resolve();
        }

        if (modalForm.modal && typeof modalForm.modal.hide === 'function') {
            modalForm.modal.hide();
            return Promise.resolve();
        }

        return Promise.resolve();
    };

    /**
     * Find the root DOM node of the modal.
     *
     * @param {ModalForm} modalForm The modal form instance.
     * @returns {HTMLElement|null}
     */
    var getModalRoot = function(modalForm) {
        if (!modalForm.modal || typeof modalForm.modal.getRoot !== 'function') {
            return null;
        }

        var jq = modalForm.modal.getRoot();
        return jq && (jq[0] || (jq.get && jq.get(0))) || null;
    };

    /**
     * Trigger a backward navigation by resubmitting the form with action=back.
     *
     * @param {ModalForm} modalForm The modal form instance.
     * @param {number} step The current wizard step.
     * @returns {Promise}
     */
    var handleBackNavigation = function(modalForm, step) {
        if (step <= 1) {
            return closeModal(modalForm);
        }

        var modalRoot = getModalRoot(modalForm);
        if (!modalRoot) {
            return closeModal(modalForm);
        }

        var form = modalRoot.querySelector('form');
        if (!form) {
            return closeModal(modalForm);
        }

        var actionInput = form.querySelector('input[name="action"]');
        if (!actionInput) {
            actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            form.appendChild(actionInput);
        }
        actionInput.value = 'back';

        var submitBtn = modalRoot.querySelector('.modal-footer .btn-primary');
        if (!submitBtn) {
            return closeModal(modalForm);
        }

        submitBtn.click();
        return Promise.resolve();
    };

    /**
     * Update the modal footer buttons after rendering.
     *
     * @param {number} step The current wizard step.
     * @param {string} backText The label for the back button.
     * @returns {Promise}
     */
    var updateModalButtons = function(step, backText) {
        var cancelBtn = document.querySelector('.modal-footer .btn-secondary');
        if (!cancelBtn) {
            return Promise.resolve();
        }

        if (step > 1) {
            cancelBtn.textContent = backText;
            return Promise.resolve();
        }

        cancelBtn.style.display = 'none';
        return Promise.resolve();
    };

    /**
     * Reopen the wizard at the next step.
     *
     * @param {ModalForm} modalForm The modal form instance.
     * @param {number} courseid The current course id.
     * @param {Object} response The submit response.
     * @returns {Promise}
     */
    var continueWizard = function(modalForm, courseid, response) {
        return closeModal(modalForm)
            .then(function() {
                Notification.addNotification({
                    message: response.message,
                    type: 'success'
                });

                return openWizard(courseid, response.nextstep, response.draftid);
            });
    };

    /**
     * Finalise the wizard submission.
     *
     * @param {ModalForm} modalForm The modal form instance.
     * @param {Object} response The submit response.
     * @returns {Promise}
     */
    var finishWizard = function(modalForm, response) {
        return closeModal(modalForm)
            .then(function() {
                Notification.addNotification({
                    message: response.message,
                    type: 'success'
                });

                return null;
            });
    };

    /**
     * Handle a successful form submission event.
     *
     * @param {ModalForm} modalForm The modal form instance.
     * @param {number} courseid The current course id.
     * @param {Object} event The form submit event.
     * @returns {Promise}
     */
    var handleSubmittedEvent = function(modalForm, courseid, event) {
        var response = event.detail || {};

        if (response.status === 'continue') {
            return continueWizard(modalForm, courseid, response);
        }

        if (response.status === 'submitted') {
            return finishWizard(modalForm, response);
        }

        return Promise.resolve();
    };

    /**
     * Open the CAT quiz wizard.
     *
     * @param {number} courseid The current course id.
     * @param {number} step The wizard step to open.
     * @param {number} draftid The draft id to continue.
     * @returns {Promise}
     */
    var openWizard = function(courseid, step, draftid) {
        step = step || 1;
        draftid = draftid || 0;

        var saveKey = step < maxSteps ? 'submitnext' : 'submitfinal';

        return Promise.all([
            Str.get_string('pluginname', 'block_catquiz_feedbackwizard'),
            Str.get_string(saveKey, 'block_catquiz_feedbackwizard'),
            Str.get_string('submitprevious', 'block_catquiz_feedbackwizard')
        ]).then(function(results) {
            var title = results[0];
            var nextText = results[1];
            var backText = results[2];

            var modalForm = new ModalForm({
                formClass: 'block_catquiz_feedbackwizard\form\wizard',
                args: {
                    courseid: courseid,
                    step: step,
                    draftid: draftid
                },
                modalConfig: {
                    title: title,
                    type: 'SAVE_CANCEL',
                    large: true,
                    scrollable: true
                },
                saveButtonText: nextText
            });

            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, function(event) {
                return handleSubmittedEvent(modalForm, courseid, event)
                    .catch(Notification.exception);
            });

            modalForm.addEventListener(modalForm.events.FORM_CANCELLED, function(event) {
                if (event.preventDefault) {
                    event.preventDefault();
                }

                return handleBackNavigation(modalForm, step).catch(Notification.exception);
            });

            return modalForm.show()
                .then(function() {
                    return updateModalButtons(step, backText);
                })
                .catch(Notification.exception);
        }).catch(Notification.exception);
    };

    return {
        /**
         * Initialise the wizard trigger.
         *
         * @param {Array} params Initialisation parameters.
         */
        init: function(params) {
            if (params && params.length > 0 && params[0].maxSteps) {
                maxSteps = parseInt(params[0].maxSteps, 10);
            }

            document.addEventListener('click', function(event) {
                var trigger = event.target.closest('.js-open-catquiz_feedbackwizard[data-action="open-wizard"]');
                if (!trigger) {
                    return;
                }

                event.preventDefault();
                var courseid = parseInt(trigger.getAttribute('data-courseid'), 10) || 0;
                openWizard(courseid, 1, 0).catch(Notification.exception);
            });
        }
    };
};

define(['core_form/modalform', 'core/notification', 'core/str'], wizardModule);
