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
        var jq;

        if (!modalForm.modal || typeof modalForm.modal.getRoot !== 'function') {
            return null;
        }

        jq = modalForm.modal.getRoot();
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
        var modalRoot = getModalRoot(modalForm);
        var form;
        var actionInput;
        var submitBtn;

        if (step <= 1) {
            return closeModal(modalForm);
        }

        if (!modalRoot) {
            return closeModal(modalForm);
        }

        form = modalRoot.querySelector('form');
        if (!form) {
            return closeModal(modalForm);
        }

        actionInput = form.querySelector('input[name="action"]');
        if (!actionInput) {
            actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            form.appendChild(actionInput);
        }
        actionInput.value = 'back';

        submitBtn = modalRoot.querySelector('.modal-footer .btn-primary');
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
        return closeModal(modalForm).then(function() {
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
        return closeModal(modalForm).then(function() {
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
     * Bind modal listeners.
     *
     * @param {ModalForm} modalForm The modal form instance.
     * @param {number} courseid The current course id.
     * @param {number} step The current wizard step.
     * @returns {void}
     */
    var bindModalEvents = function(modalForm, courseid, step) {
        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, function(event) {
            handleSubmittedEvent(modalForm, courseid, event).catch(Notification.exception);
        });

        modalForm.addEventListener(modalForm.events.FORM_CANCELLED, function(event) {
            if (event.preventDefault) {
                event.preventDefault();
            }

            handleBackNavigation(modalForm, step).catch(Notification.exception);
        });
    };

    /**
     * Create the modal form instance.
     *
     * @param {number} courseid The current course id.
     * @param {number} step The wizard step to open.
     * @param {number} draftid The draft id to continue.
     * @param {string} title The modal title.
     * @param {string} nextText The submit button text.
     * @returns {ModalForm}
     */
    var createModalForm = function(courseid, step, draftid, title, nextText) {
        return new ModalForm({
            formClass: 'block_catquiz_feedbackwizard\\form\\wizard',
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
    };

    /**
     * Show the modal form and update its buttons.
     *
     * @param {ModalForm} modalForm The modal form instance.
     * @param {number} step The current wizard step.
     * @param {string} backText The back button text.
     * @returns {Promise}
     */
    var showModalForm = function(modalForm, step, backText) {
        return modalForm.show().then(function() {
            return updateModalButtons(step, backText);
        });
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
        var saveKey;

        step = step || 1;
        draftid = draftid || 0;
        saveKey = step < maxSteps ? 'submitnext' : 'submitfinal';

        return Promise.all([
            Str.get_string('pluginname', 'block_catquiz_feedbackwizard'),
            Str.get_string(saveKey, 'block_catquiz_feedbackwizard'),
            Str.get_string('submitprevious', 'block_catquiz_feedbackwizard')
        ]).then(function(results) {
            var modalForm = createModalForm(courseid, step, draftid, results[0], results[1]);

            bindModalEvents(modalForm, courseid, step);
            return showModalForm(modalForm, step, results[2]);
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
                openWizard(parseInt(trigger.getAttribute('data-courseid'), 10) || 0, 1, 0)
                    .catch(Notification.exception);
            });
        }
    };
};

/**
 * Export the wizard AMD module.
 *
 * @param {Function} ModalForm Moodle modal form constructor.
 * @param {Object} Notification Moodle notification helper.
 * @param {Object} Str Moodle string helper.
 * @returns {Object}
 */
define(['core_form/modalform', 'core/notification', 'core/str'], function(ModalForm, Notification, Str) {
    return wizardModule(ModalForm, Notification, Str);
});
