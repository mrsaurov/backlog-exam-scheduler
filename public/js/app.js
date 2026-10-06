/*
 * Shared behaviour for every page: one styled dialog used for
 * confirmations and short notices instead of browser pop-ups, and
 * floating labels for elements marked data-bs-toggle="tooltip".
 *
 *   confirmAction({ title, message, confirmLabel, danger }, onConfirm)
 *   showNotice(message, title)
 *   submitForm(form)
 *
 * Markup can also ask for a confirmation before a form is submitted or a
 * link is followed:
 *
 *   <button type="submit" name="submit" value="delete"
 *           data-confirm="This cannot be undone."
 *           data-confirm-title="Delete this exam?"
 *           data-confirm-button="Delete exam">
 */
(function () {
    'use strict';

    var dialogEl = null;
    var dialog = null;
    var pendingConfirm = null;

    function ensureDialog() {
        if (dialog) {
            return true;
        }

        dialogEl = document.getElementById('appDialog');
        if (!dialogEl || !window.bootstrap) {
            return false;
        }

        dialog = bootstrap.Modal.getOrCreateInstance(dialogEl);

        dialogEl.querySelector('[data-dialog-confirm]').addEventListener('click', function () {
            var callback = pendingConfirm;
            pendingConfirm = null;
            dialog.hide();
            if (callback) {
                callback();
            }
        });

        dialogEl.addEventListener('shown.bs.modal', function () {
            // Stay above a modal that is already open
            var backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 1) {
                backdrops[backdrops.length - 1].style.zIndex = 1060;
            }
        });

        dialogEl.addEventListener('hidden.bs.modal', function () {
            pendingConfirm = null;
            // Keep the page locked while another modal is still open underneath
            if (document.querySelector('.modal.show')) {
                document.body.classList.add('modal-open');
            }
        });

        return true;
    }

    function openDialog(options, onConfirm) {
        var confirmButton = dialogEl.querySelector('[data-dialog-confirm]');
        var cancelButton = dialogEl.querySelector('[data-dialog-cancel]');

        dialogEl.querySelector('[data-dialog-title]').textContent = options.title || 'Are you sure?';
        dialogEl.querySelector('[data-dialog-message]').textContent = options.message || '';

        confirmButton.textContent = options.confirmLabel || 'Confirm';
        confirmButton.className = 'btn ' + (options.danger ? 'btn-danger' : 'btn-primary');
        cancelButton.hidden = !!options.noticeOnly;

        pendingConfirm = onConfirm || null;
        dialog.show();
    }

    window.confirmAction = function (options, onConfirm) {
        if (typeof options === 'string') {
            options = { message: options };
        }

        if (!ensureDialog()) {
            if (window.confirm(options.message) && onConfirm) {
                onConfirm();
            }
            return;
        }

        openDialog(options, onConfirm);
    };

    window.showNotice = function (message, title) {
        if (!ensureDialog()) {
            window.alert(message);
            return;
        }

        openDialog({
            title: title || 'Check this first',
            message: message,
            confirmLabel: 'OK',
            noticeOnly: true
        });
    };

    function optionsFrom(element) {
        return {
            title: element.dataset.confirmTitle,
            message: element.dataset.confirm,
            confirmLabel: element.dataset.confirmButton,
            danger: element.dataset.confirmStyle !== 'primary'
        };
    }

    // Submit a form from script. Forms here often contain a field named "submit",
    // which hides the form's own submit() method, so call the native one directly.
    window.submitForm = function (form) {
        HTMLFormElement.prototype.submit.call(form);
    };

    // A scripted submit does not send the clicked button, so carry its name/value along
    function submitWith(form, submitter) {
        if (submitter && submitter.name) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = submitter.name;
            hidden.value = submitter.value;
            form.appendChild(hidden);
        }
        window.submitForm(form);
    }

    // Floating labels, used by course code chips to show the course title.
    // Delegated from the body so chips in hidden tabs and filtered rows work too.
    document.addEventListener('DOMContentLoaded', function () {
        if (window.bootstrap && bootstrap.Tooltip) {
            new bootstrap.Tooltip(document.body, {
                selector: '[data-bs-toggle="tooltip"]',
                placement: 'top',
                delay: { show: 100, hide: 0 }
            });
        }
    });

    // Exam section tabs scroll sideways on small screens: keep the current
    // tab in view and mark the strip while tabs are hidden to the right.
    document.addEventListener('DOMContentLoaded', function () {
        var tabs = document.querySelector('.workspace-tabs');
        if (!tabs) {
            return;
        }

        var active = tabs.querySelector('.is-active');
        if (active) {
            tabs.scrollLeft = active.offsetLeft - (tabs.clientWidth - active.offsetWidth) / 2;
        }

        function markOverflow() {
            tabs.classList.toggle('has-more', tabs.scrollWidth - tabs.scrollLeft - tabs.clientWidth > 4);
        }

        tabs.addEventListener('scroll', markOverflow);
        window.addEventListener('resize', markOverflow);
        markOverflow();
    });

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-confirm]');
        if (!trigger || trigger.tagName === 'FORM') {
            return;
        }

        event.preventDefault();
        window.confirmAction(optionsFrom(trigger), function () {
            if (trigger.tagName === 'A') {
                window.location.href = trigger.href;
                return;
            }

            var form = trigger.form || trigger.closest('form');
            if (form) {
                submitWith(form, trigger);
            }
        });
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches || !form.matches('form[data-confirm]')) {
            return;
        }

        event.preventDefault();
        var submitter = event.submitter;
        window.confirmAction(optionsFrom(form), function () {
            submitWith(form, submitter);
        });
    });
})();
