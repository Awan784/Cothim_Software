(function () {
    'use strict';

    function initAlerts() {
        document.querySelectorAll('.ams-alert [data-bs-dismiss="alert"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var alert = btn.closest('.ams-alert');
                if (alert) {
                    alert.classList.add('is-hiding');
                    setTimeout(function () { alert.remove(); }, 350);
                }
            });
        });

        setTimeout(function () {
            document.querySelectorAll('.ams-alert[data-auto-dismiss="true"]').forEach(function (el) {
                el.classList.add('is-hiding');
                setTimeout(function () { el.remove(); }, 350);
            });
        }, 5500);
    }

    function initFormSubmitGuard() {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.dataset.amsNoGuard === 'true') {
                return;
            }

            form.addEventListener('submit', function (e) {
                setTimeout(function () {
                    if (e.defaultPrevented) {
                        return;
                    }

                    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
                        if (btn.disabled || btn.classList.contains('is-loading')) {
                            return;
                        }
                        btn.classList.add('is-loading');
                        btn.disabled = true;
                        if (!btn.style.position) {
                            btn.style.position = 'relative';
                        }
                    });
                }, 0);
            });
        });
    }

    function initConfirmDeletes() {
        document.querySelectorAll('form[method="post"] button[type="submit"].btn-outline-danger, form[method="post"] button[type="submit"].btn-danger').forEach(function (btn) {
            if (btn.dataset.amsConfirmBound) {
                return;
            }
            btn.dataset.amsConfirmBound = '1';
            var form = btn.closest('form');
            if (!form || !form.querySelector('input[name="_method"][value="delete"], input[name="_method"][value="DELETE"]')) {
                return;
            }
            form.addEventListener('submit', function (e) {
                if (form.dataset.amsConfirmed === '1') {
                    return;
                }
                var msg = btn.getAttribute('onclick');
                if (msg && msg.indexOf('confirm') !== -1) {
                    return;
                }
                if (!window.confirm('Are you sure you want to delete this record?')) {
                    e.preventDefault();
                    form.querySelectorAll('button[type="submit"]').forEach(function (b) {
                        b.classList.remove('is-loading');
                        b.disabled = false;
                    });
                } else {
                    form.dataset.amsConfirmed = '1';
                }
            });
        });
    }

    function initSidebarScroll() {
        var active = document.querySelector('#sidebarMenu .nav-link.active');
        if (active && active.scrollIntoView) {
            setTimeout(function () {
                active.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }, 300);
        }
    }

    /** Keep modals on body so fixed positioning and clicks work (no trapped backdrop). */
    function initModals() {
        document.querySelectorAll('.modal').forEach(function (modalEl) {
            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        });

        document.addEventListener('hidden.bs.modal', function () {
            if (document.querySelector('.modal.show')) {
                return;
            }
            document.querySelectorAll('.modal-backdrop').forEach(function (el) {
                el.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        });
    }

    function stripAmountValue(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }

        var cleaned = String(value).replace(/,/g, '');
        var parts = cleaned.split('.');
        var intPart = parts[0].replace(/\D/g, '');
        var decPart = parts.length > 1 ? parts.slice(1).join('').replace(/\D/g, '').slice(0, 2) : '';

        if (parts.length > 1) {
            return intPart + '.' + decPart;
        }

        return intPart;
    }

    function formatAmountDisplay(value) {
        var raw = stripAmountValue(value);

        if (!raw) {
            return '';
        }

        var parts = raw.split('.');
        var intPart = parts[0] || '0';
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        if (parts.length > 1) {
            return intPart + '.' + parts[1];
        }

        return intPart;
    }

    function bindAmountInput(input) {
        if (input.dataset.amountInit === '1') {
            return;
        }

        input.dataset.amountInit = '1';
        input.setAttribute('inputmode', 'decimal');
        input.setAttribute('autocomplete', 'off');

        if (input.value) {
            input.value = formatAmountDisplay(input.value);
        }

        input.addEventListener('input', function () {
            var selectionStart = input.selectionStart || 0;
            var oldValue = input.value;
            var digitsBeforeCursor = stripAmountValue(oldValue.slice(0, selectionStart)).replace(/\./g, '').length;
            var formatted = formatAmountDisplay(oldValue);

            input.value = formatted;

            var pos = 0;
            var digitsSeen = 0;

            while (pos < formatted.length && digitsSeen < digitsBeforeCursor) {
                if (/\d/.test(formatted.charAt(pos))) {
                    digitsSeen++;
                }
                pos++;
            }

            input.setSelectionRange(pos, pos);
        });

        input.addEventListener('blur', function () {
            if (!input.value) {
                return;
            }

            var raw = stripAmountValue(input.value);
            if (!raw) {
                input.value = '';
                return;
            }

            if (raw.indexOf('.') === -1) {
                input.value = formatAmountDisplay(raw);
                return;
            }

            var parts = raw.split('.');
            var decimals = (parts[1] || '').padEnd(2, '0').slice(0, 2);
            input.value = formatAmountDisplay(parts[0] + '.' + decimals);
        });
    }

    function initAmountInputs(root) {
        var scope = root || document;

        scope.querySelectorAll('.ams-amount-input').forEach(bindAmountInput);

        scope.querySelectorAll('form').forEach(function (form) {
            if (form.dataset.amsAmountSubmitBound === '1') {
                return;
            }

            form.dataset.amsAmountSubmitBound = '1';

            form.addEventListener('submit', function () {
                form.querySelectorAll('.ams-amount-input').forEach(function (input) {
                    input.value = stripAmountValue(input.value);
                });
            }, true);
        });
    }

    function initAmsDatePickers(root) {
        if (typeof window.flatpickr !== 'function') {
            return;
        }

        var scope = root || document;

        scope.querySelectorAll('.ams-date-input:not([data-fp-init])').forEach(function (input) {
            input.dataset.fpInit = '1';
            var modal = input.closest('.modal');
            var options = {
                dateFormat: 'd-m-y',
                allowInput: true,
                disableMobile: true,
                clickOpens: true,
            };

            if (modal) {
                options.appendTo = modal;
            }

            window.flatpickr(input, options);
        });

        scope.querySelectorAll('.ams-datetime-input:not([data-fp-init])').forEach(function (input) {
            input.dataset.fpInit = '1';
            var modal = input.closest('.modal');
            var options = {
                dateFormat: 'd-m-y H:i',
                enableTime: true,
                time_24hr: true,
                allowInput: true,
                disableMobile: true,
                clickOpens: true,
            };

            if (modal) {
                options.appendTo = modal;
            }

            window.flatpickr(input, options);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAlerts();
        initFormSubmitGuard();
        initConfirmDeletes();
        initSidebarScroll();
        initModals();
        initAmsDatePickers();
        initAmountInputs();
    });

    document.addEventListener('shown.bs.modal', function (event) {
        initAmsDatePickers(event.target);
        initAmountInputs(event.target);
    });
})();
