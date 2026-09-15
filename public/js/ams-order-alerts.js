(function () {
    'use strict';

    var config = window.amsOrderAlerts;
    if (!config || !config.feedUrl) {
        return;
    }

    var SEEN_KEY = 'amsPendingOrderSeenIds';
    var pollMs = 2000;
    var alerting = false;
    var audio = document.getElementById('amsOrderRingtone');
    var soundReady = false;

    if (audio) {
        audio.volume = 1;
        audio.loop = true;
        audio.muted = true;
    }

    unlockSound();
    ['click', 'pointerdown', 'pointermove', 'keydown', 'touchstart', 'mousemove'].forEach(function (eventName) {
        document.addEventListener(eventName, unlockSound, true);
    });
    poll();
    setInterval(poll, pollMs);

    function unlockSound() {
        if (!audio || soundReady) {
            return;
        }
        var play = audio.play();
        if (play && play.then) {
            play.then(function () {
                if (!alerting) {
                    audio.pause();
                    audio.currentTime = 0;
                }
                audio.muted = false;
                soundReady = true;
            }).catch(function () {});
        }
    }

    function startRingtone() {
        if (!audio) {
            return;
        }
        audio.muted = false;
        audio.loop = true;
        audio.currentTime = 0;
        var play = audio.play();
        if (play && play.catch) {
            play.catch(function () {
                audio.muted = true;
                audio.play().then(function () {
                    audio.muted = false;
                }).catch(function () {});
            });
        }
    }

    function stopRingtone() {
        if (!audio) {
            return;
        }
        audio.pause();
        audio.currentTime = 0;
    }

    function loadSeen() {
        try {
            return new Set(JSON.parse(localStorage.getItem(SEEN_KEY) || '[]').map(Number));
        } catch (e) {
            return new Set();
        }
    }

    function saveSeen(ids) {
        localStorage.setItem(SEEN_KEY, JSON.stringify(Array.from(ids)));
    }

    function poll() {
        fetch(config.feedUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) {
                return null;
            }
            return response.json();
        }).then(function (data) {
            if (!data) {
                return;
            }
            var orders = data.orders || [];
            updateBadges(data.count || 0);
            renderLiveOrders(orders, data.count || 0);
            handleNewOrders(orders);
        }).catch(function () {});
    }

    function handleNewOrders(orders) {
        var seen = loadSeen();
        var incoming = orders.map(function (order) { return Number(order.id); });
        var isFirstLoad = localStorage.getItem(SEEN_KEY) === null;

        if (isFirstLoad) {
            saveSeen(new Set(incoming));
            return;
        }

        var fresh = orders.filter(function (order) {
            return !seen.has(Number(order.id));
        });

        incoming.forEach(function (id) { seen.add(id); });
        saveSeen(seen);

        if (fresh.length === 0 || alerting) {
            return;
        }

        announce(fresh);
    }

    function announce(orders) {
        alerting = true;
        unlockSound();
        startRingtone();
        highlightLivePanel(true);

        var first = orders[0];
        var title = orders.length === 1 ? 'New order received' : orders.length + ' new orders received';
        var text = orders.length === 1
            ? first.order_no + ' from ' + first.salesman + ' — ' + first.customer + ' (' + first.total + ')'
            : orders.map(function (order) {
                return order.order_no + ' · ' + order.salesman;
            }).join('\n');

        var go = function () {
            stopRingtone();
            highlightLivePanel(false);
            alerting = false;
            window.location.href = orders.length === 1 ? first.url : config.indexUrl;
        };

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                title: title,
                html: '<div style="font-size:1rem;line-height:1.5;">' + escapeHtml(text).replace(/\n/g, '<br>') + '</div>',
                icon: 'success',
                confirmButtonText: orders.length === 1 ? 'Open order' : 'View orders',
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then(go);
            return;
        }

        if (window.confirm(title + '\n\n' + text)) {
            go();
            return;
        }

        stopRingtone();
        highlightLivePanel(false);
        alerting = false;
    }

    function highlightLivePanel(on) {
        var panel = document.getElementById('amsLiveOrdersPanel');
        if (!panel) {
            return;
        }
        panel.classList.toggle('is-ringing', on);
    }

    function updateBadges(count) {
        var badge = document.getElementById('amsPendingOrderBadge');
        if (badge) {
            badge.textContent = String(count);
            badge.hidden = count < 1;
        }

        var topItem = document.getElementById('amsPendingOrderTopItem');
        var topCount = document.getElementById('amsPendingOrderTopCount');
        var topLabel = document.getElementById('amsPendingOrderTopLabel');
        if (topItem) {
            topItem.hidden = count < 1;
        }
        if (topCount) {
            topCount.textContent = String(count);
        }
        if (topLabel) {
            topLabel.textContent = count === 1 ? 'pending order' : 'pending orders';
        }
    }

    function renderLiveOrders(orders, count) {
        var banner = document.getElementById('amsLiveOrdersBanner');
        var bannerCount = document.getElementById('amsLiveOrdersBannerCount');
        var bannerPlural = document.getElementById('amsLiveOrdersBannerPlural');
        var panel = document.getElementById('amsLiveOrdersPanel');
        var body = document.getElementById('amsLiveOrdersBody');

        if (banner) {
            banner.hidden = count < 1;
        }
        if (bannerCount) {
            bannerCount.textContent = String(count);
        }
        if (bannerPlural) {
            bannerPlural.textContent = count === 1 ? '' : 's';
        }
        if (panel) {
            panel.hidden = count < 1;
        }
        if (!body) {
            return;
        }

        if (orders.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No pending salesman orders.</td></tr>';
            return;
        }

        body.innerHTML = orders.map(function (order, index) {
            return '<tr data-order-id="' + order.id + '" class="' + (index === 0 ? 'ams-live-order-row is-new' : '') + '">' +
                '<td class="fw-semibold"><a href="' + escapeAttr(order.url) + '">' + escapeHtml(order.order_no) + '</a></td>' +
                '<td>' + escapeHtml(order.salesman) + '</td>' +
                '<td>' + escapeHtml(order.customer) + '</td>' +
                '<td class="text-end">' + escapeHtml(order.total) + '</td>' +
                '<td class="text-end"><a class="btn btn-sm btn-primary" href="' + escapeAttr(order.url) + '">Open</a></td>' +
                '</tr>';
        }).join('');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/'/g, '&#39;');
    }
})();
