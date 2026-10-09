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

    function initMobileSidebar() {
        var sidebar = document.getElementById('sidebarMenu');
        if (!sidebar) {
            return;
        }

        sidebar.querySelectorAll('a.nav-link:not([data-bs-toggle])').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth >= 992 || !window.bootstrap || !window.bootstrap.Collapse) {
                    return;
                }
                var instance = window.bootstrap.Collapse.getInstance(sidebar);
                if (instance) {
                    instance.hide();
                }
            });
        });
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

    function bindAmsPicker(input, extra) {
        if (!input || input._flatpickr || input.dataset.fpInit === '1') {
            return;
        }

        var modal = input.closest('.modal');
        if (modal && !modal.classList.contains('show')) {
            return;
        }

        input.dataset.fpInit = '1';

        var options = {
            dateFormat: extra.dateFormat,
            allowInput: true,
            disableMobile: true,
            clickOpens: true,
            allowInvalidPreload: true,
        };

        if (extra.enableTime) {
            options.enableTime = true;
            options.time_24hr = true;
        }

        if (modal) {
            options.static = true;
            options.appendTo = input.closest('.ams-date-field') || input.parentElement;
        }

        window.flatpickr(input, options);
    }

    function initAmsDatePickers(root) {
        if (typeof window.flatpickr !== 'function') {
            return;
        }

        var scope = root || document;

        scope.querySelectorAll('.ams-date-input').forEach(function (input) {
            bindAmsPicker(input, { dateFormat: 'd-m-Y' });
        });

        scope.querySelectorAll('.ams-datetime-input').forEach(function (input) {
            bindAmsPicker(input, { dateFormat: 'd-m-Y H:i', enableTime: true });
        });
    }

    function shouldSearchSelect(select) {
        if (!select || select.tagName !== 'SELECT') {
            return false;
        }
        if (select.dataset.amsPlain === '1' || select.multiple) {
            return false;
        }
        if (select.dataset.amsCombo === '1' || select.closest('.ams-combo') || select.closest('.choices')) {
            return false;
        }

        var modal = select.closest('.modal');
        if (modal && !modal.classList.contains('show')) {
            return false;
        }

        if (select.dataset.amsSearch === '1' || select.classList.contains('ams-search-select')) {
            return true;
        }

        return select.options.length >= 6;
    }

    function selectedOptionLabel(select) {
        var option = select.options[select.selectedIndex];
        if (!option || option.value === '') {
            return '';
        }
        return (option.textContent || '').trim();
    }

    function closeAllCombos(except) {
        document.querySelectorAll('.ams-combo.is-open').forEach(function (combo) {
            if (except && combo === except) {
                return;
            }
            combo.classList.remove('is-open');
            var search = combo.querySelector('.ams-combo-search');
            if (search) {
                search.value = '';
            }
        });
    }

    function enhanceSearchSelect(select) {
        if (!shouldSearchSelect(select)) {
            return;
        }

        select.dataset.amsCombo = '1';
        select.classList.add('ams-combo-native');
        select.setAttribute('tabindex', '-1');
        select.setAttribute('aria-hidden', 'true');

        var wrap = document.createElement('div');
        wrap.className = 'ams-combo' + (select.classList.contains('form-select-sm') ? ' ams-combo-sm' : '');
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        var placeholder = select.getAttribute('data-search-placeholder') || 'Type to search';
        var emptyLabel = (select.options[0] && select.options[0].value === '')
            ? (select.options[0].textContent || '').trim()
            : 'Select';

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'ams-combo-toggle';
        toggle.setAttribute('aria-haspopup', 'listbox');
        toggle.setAttribute('aria-expanded', 'false');

        var panel = document.createElement('div');
        panel.className = 'ams-combo-panel';

        var search = document.createElement('input');
        search.type = 'search';
        search.className = 'ams-combo-search';
        search.placeholder = placeholder;
        search.autocomplete = 'off';
        search.setAttribute('aria-label', placeholder);

        var list = document.createElement('div');
        list.className = 'ams-combo-list';
        list.setAttribute('role', 'listbox');

        panel.appendChild(search);
        panel.appendChild(list);
        wrap.appendChild(toggle);
        wrap.appendChild(panel);

        function syncToggle() {
            var label = selectedOptionLabel(select);
            toggle.textContent = label || emptyLabel;
            toggle.classList.toggle('is-placeholder', !label);
        }

        function visibleOptions(query) {
            var q = (query || '').trim().toLowerCase();
            var matches = [];
            Array.prototype.forEach.call(select.options, function (option, index) {
                if (option.value === '') {
                    return;
                }
                var label = (option.textContent || '').trim();
                if (!q || label.toLowerCase().indexOf(q) !== -1) {
                    matches.push({ option: option, index: index, label: label });
                }
            });
            return matches;
        }

        function renderList(query, activeValue) {
            var matches = visibleOptions(query);
            list.innerHTML = '';

            if (matches.length === 0) {
                var empty = document.createElement('div');
                empty.className = 'ams-combo-empty';
                empty.textContent = 'No matches';
                list.appendChild(empty);
                return;
            }

            matches.forEach(function (item, i) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ams-combo-option';
                btn.setAttribute('role', 'option');
                btn.dataset.value = item.option.value;
                btn.dataset.index = String(item.index);
                btn.textContent = item.label;
                if (item.option.value === select.value || (!activeValue && i === 0 && query)) {
                    btn.classList.add('is-active');
                }
                if (item.option.value === select.value) {
                    btn.classList.add('is-selected');
                }
                list.appendChild(btn);
            });
        }

        function openCombo() {
            closeAllCombos(wrap);
            wrap.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            search.value = '';
            renderList('');
            window.setTimeout(function () {
                search.focus();
                search.select();
            }, 0);
        }

        function closeCombo() {
            wrap.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            search.value = '';
        }

        function pickValue(value) {
            if (select.value !== value) {
                select.value = value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            syncToggle();
            closeCombo();
        }

        function activeOption() {
            return list.querySelector('.ams-combo-option.is-active') || list.querySelector('.ams-combo-option');
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            if (wrap.classList.contains('is-open')) {
                closeCombo();
            } else {
                openCombo();
            }
        });

        search.addEventListener('input', function () {
            renderList(search.value);
        });

        search.addEventListener('keydown', function (event) {
            var current = activeOption();
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                var options = list.querySelectorAll('.ams-combo-option');
                if (!options.length) {
                    return;
                }
                var index = Array.prototype.indexOf.call(options, current);
                if (event.key === 'ArrowDown') {
                    index = index < 0 ? 0 : Math.min(options.length - 1, index + 1);
                } else {
                    index = index < 0 ? options.length - 1 : Math.max(0, index - 1);
                }
                Array.prototype.forEach.call(options, function (el) { el.classList.remove('is-active'); });
                options[index].classList.add('is-active');
                options[index].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (current) {
                    pickValue(current.dataset.value);
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closeCombo();
                toggle.focus();
            }
        });

        list.addEventListener('mousedown', function (event) {
            var option = event.target.closest('.ams-combo-option');
            if (!option) {
                return;
            }
            event.preventDefault();
            pickValue(option.dataset.value);
        });

        wrap._amsCloseCombo = closeCombo;
        wrap._amsSyncCombo = syncToggle;
        syncToggle();
    }

    function initSearchSelects(root) {
        var scope = root || document;
        var selects = scope.matches && scope.matches('select')
            ? [scope]
            : Array.prototype.slice.call(scope.querySelectorAll('select.form-select'));

        selects.forEach(enhanceSearchSelect);
    }

    function stripChoicesFromClone(root) {
        if (!root) {
            return;
        }

        root.querySelectorAll('.ams-combo').forEach(function (wrap) {
            var select = wrap.querySelector('select');
            if (select) {
                select.classList.remove('ams-combo-native');
                select.removeAttribute('tabindex');
                select.removeAttribute('aria-hidden');
                delete select.dataset.amsCombo;
                wrap.parentNode.insertBefore(select, wrap);
            }
            wrap.remove();
        });

        root.querySelectorAll('.choices').forEach(function (wrap) {
            var select = wrap.querySelector('select');
            if (select) {
                select.removeAttribute('hidden');
                select.removeAttribute('data-choice');
                select.style.display = '';
                select.style.visibility = '';
                select.classList.remove('choices__input');
                delete select.dataset.amsChoices;
                select._amsChoices = null;
                wrap.parentNode.insertBefore(select, wrap);
            }
            wrap.remove();
        });
    }

    function destroySearchSelects(root) {
        stripChoicesFromClone(root);
    }

    if (!window._amsComboDocBound) {
        window._amsComboDocBound = true;
        document.addEventListener('mousedown', function (event) {
            if (event.target.closest('.ams-combo')) {
                return;
            }
            closeAllCombos();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAllCombos();
            }
        });
    }

    window.amsInitSearchSelects = initSearchSelects;
    window.amsStripChoicesFromClone = stripChoicesFromClone;
    window.amsDestroySearchSelects = destroySearchSelects;
    window.amsInitDatePickers = initAmsDatePickers;

    document.addEventListener('DOMContentLoaded', function () {
        initAlerts();
        initFormSubmitGuard();
        initConfirmDeletes();
        initSidebarScroll();
        initMobileSidebar();
        initModals();
        initAmsDatePickers();
        initAmountInputs();
        initSearchSelects();
    });

    document.addEventListener('shown.bs.modal', function (event) {
        initAmsDatePickers(event.target);
        initAmountInputs(event.target);
        initSearchSelects(event.target);
    });

    document.addEventListener('hide.bs.modal', function (event) {
        event.target.querySelectorAll('.ams-date-input, .ams-datetime-input').forEach(function (input) {
            if (input._flatpickr) {
                input._flatpickr.close();
            }
        });
    });
})();
