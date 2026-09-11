(function () {
    var root = document.getElementById('ams-assistant');
    if (!root) return;

    var panel = document.getElementById('ams-assistant-panel');
    var toggle = document.getElementById('ams-assistant-toggle');
    var closeBtn = document.getElementById('ams-assistant-close');
    var form = document.getElementById('ams-assistant-form');
    var input = document.getElementById('ams-assistant-input');
    var messages = document.getElementById('ams-assistant-messages');
    var pendingBox = document.getElementById('ams-assistant-pending');
    var pendingText = document.getElementById('ams-assistant-pending-text');
    var confirmBtn = document.getElementById('ams-assistant-confirm');
    var cancelBtn = document.getElementById('ams-assistant-cancel');

    var history = [];
    var csrf = root.dataset.csrf || '';
    var sending = false;
    var restoring = false;
    var storageKey = 'ams-assistant-u' + (root.dataset.userId || 'guest');
    var maxHistory = 8;
    var maxBubbles = 80;

    function addBubble(text, who) {
        var div = document.createElement('div');
        div.className = 'ams-assistant-bubble ' + who;
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        if (!restoring) {
            saveState();
        }
        return div;
    }

    function collectBubbles() {
        var bubbles = [];
        messages.querySelectorAll('.ams-assistant-bubble').forEach(function (el) {
            if (el.textContent === 'Working…') return;
            bubbles.push({
                who: el.classList.contains('user') ? 'user' : 'bot',
                text: el.textContent,
            });
        });
        if (bubbles.length > maxBubbles) {
            bubbles = bubbles.slice(-maxBubbles);
        }
        return bubbles;
    }

    function saveState() {
        try {
            localStorage.setItem(storageKey, JSON.stringify({
                v: 1,
                open: !panel.hidden,
                history: history,
                messages: collectBubbles(),
            }));
        } catch (e) {}
    }

    function loadState() {
        try {
            var raw = localStorage.getItem(storageKey);
            if (!raw) return;
            var data = JSON.parse(raw);
            if (data.history && Array.isArray(data.history)) {
                history = data.history.filter(function (item) {
                    return item && (item.role === 'user' || item.role === 'assistant') && typeof item.content === 'string';
                });
            }
            if (data.messages && data.messages.length) {
                restoring = true;
                messages.innerHTML = '';
                data.messages.forEach(function (item) {
                    if (!item || !item.text) return;
                    addBubble(item.text, item.who === 'user' ? 'user' : 'bot');
                });
                restoring = false;
            }
            if (data.open) {
                panel.hidden = false;
            }
        } catch (e) {
            restoring = false;
        }
    }

    function setPending(pending) {
        if (pending && pending.summary) {
            pendingBox.hidden = false;
            pendingText.textContent = pending.summary;
        } else {
            pendingBox.hidden = true;
            pendingText.textContent = '';
        }
    }

    function request(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body || {}),
        }).then(function (res) {
            return res.text().then(function (text) {
                var data = {};
                if (text) {
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error(res.ok ? 'The assistant returned an invalid response.' : 'Request failed (' + res.status + ').');
                    }
                }
                if (!res.ok) {
                    throw new Error(data.message || data.reply || 'Request failed (' + res.status + ').');
                }
                return data;
            });
        });
    }

    function handleResult(data, userText) {
        if (userText) {
            history.push({ role: 'user', content: userText });
        }
        var reply = data.reply || 'Done.';
        addBubble(reply, 'bot');
        history.push({ role: 'assistant', content: reply });
        if (history.length > maxHistory) {
            history = history.slice(-maxHistory);
        }
        setPending(data.pending);
        saveState();
    }

    function setOpen(open) {
        panel.hidden = !open;
        saveState();
        if (open) {
            input.focus();
            messages.scrollTop = messages.scrollHeight;
        }
    }

    loadState();

    try {
        var pending = root.dataset.pending ? JSON.parse(root.dataset.pending) : null;
        setPending(pending);
    } catch (e) {}

    toggle.addEventListener('click', function () {
        setOpen(panel.hidden);
    });

    closeBtn.addEventListener('click', function () {
        setOpen(false);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = (input.value || '').trim();
        if (!text || sending) return;

        addBubble(text, 'user');
        input.value = '';
        sending = true;
        input.disabled = true;
        var working = addBubble('Working…', 'bot');

        request(root.dataset.chatUrl, { message: text, history: history })
            .then(function (data) {
                working.remove();
                handleResult(data, text);
            })
            .catch(function (err) {
                working.remove();
                addBubble(err.message || 'Could not send.', 'bot');
            })
            .finally(function () {
                sending = false;
                input.disabled = false;
                input.focus();
            });
    });

    confirmBtn.addEventListener('click', function () {
        if (sending) return;
        sending = true;
        confirmBtn.disabled = true;
        request(root.dataset.confirmUrl, {})
            .then(function (data) { handleResult(data, 'yes'); })
            .catch(function (err) { addBubble(err.message || 'Could not confirm.', 'bot'); })
            .finally(function () {
                sending = false;
                confirmBtn.disabled = false;
            });
    });

    cancelBtn.addEventListener('click', function () {
        request(root.dataset.cancelUrl, {})
            .then(function (data) { handleResult(data, 'cancel'); })
            .catch(function (err) { addBubble(err.message || 'Could not cancel.', 'bot'); });
    });
})();
