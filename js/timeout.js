(function () {
    'use strict';
    if (window.fullcareIdleLoaded) return;
    window.fullcareIdleLoaded = true;
    function start() {
        const node = document.getElementById('fullcare-idle-config');
        if (!node) return;
        const config = JSON.parse(node.textContent);
        const storageKey = 'fullcare-idle:' + config.channel;
        let deadline = Date.now() + (config.expiresAt - config.serverTime) * 1000;
        let lastRequest = 0, lastStatus = Date.now();
        let pendingActivity = false, busy = false, leaving = false;
        const notice = document.createElement('aside');
        notice.id = 'fullcare-idle-warning';
        notice.hidden = true;
        notice.setAttribute('role', 'region');
        notice.setAttribute('aria-label', 'Aviso de inatividade');
        notice.innerHTML = '<h2 role="status" aria-live="polite">Sua sessão vai expirar</h2>' +
            '<p>Sua sessão será encerrada em <strong data-idle-countdown></strong> por inatividade. ' +
            'Continue usando o sistema para permanecer conectado.</p>' +
            '<button type="button">Continuar conectado</button>' +
            '<p data-idle-error role="status" hidden></p>';
        document.body.appendChild(notice);
        const countdown = notice.querySelector('[data-idle-countdown]');
        const error = notice.querySelector('[data-idle-error]');
        const button = notice.querySelector('button');
        function publish(value) {
            try { localStorage.setItem(storageKey, JSON.stringify(value)); } catch (_) { /* opcional */ }
        }
        function leave(broadcast) {
            if (leaving) return;
            leaving = true;
            if (broadcast) publish({ expired: true });
            window.location.replace(config.loginUrl);
        }
        function render() {
            const seconds = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
            notice.hidden = seconds > config.warningSeconds;
            countdown.textContent = Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
        }
        async function sync(activity) {
            if (busy || leaving) return;
            busy = true;
            lastStatus = Date.now();
            if (activity) { pendingActivity = false; lastRequest = Date.now(); button.disabled = true; }
            try {
                const response = await fetch(config.endpoint, {
                    method: activity ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store',
                    signal: AbortSignal.timeout(10000),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                        ...(activity ? { 'X-CSRF-Token': config.csrf } : {}) }
                });
                if (response.status === 401) { leave(true); return; }
                if (!response.ok) throw new Error('session status');
                const data = await response.json();
                if (data.status !== 'ok' || !Number.isFinite(data.expiresAt) || !Number.isFinite(data.serverTime)) {
                    throw new Error('invalid session status');
                }
                deadline = Date.now() + Math.max(0, data.expiresAt - data.serverTime) * 1000;
                publish({ deadline: deadline });
                error.hidden = true;
                render();
            } catch (_) {
                if (activity) {
                    error.textContent = 'Não foi possível confirmar sua atividade. Verifique a conexão e tente novamente.';
                    error.hidden = false;
                }
                // Uma falha de rede nunca concede mais tempo à sessão.
                if (Date.now() >= deadline) leave(false);
            } finally { busy = false; button.disabled = false; }
        }
        function activity(event) {
            if (!event.isTrusted || document.visibilityState !== 'visible' || leaving) return;
            if (Date.now() >= deadline) { sync(false); return; }
            pendingActivity = true;
            if (Date.now() - lastRequest >= 10000) sync(true);
        }
        ['pointermove', 'pointerdown', 'keydown', 'touchstart', 'wheel', 'input'].forEach(function (name) {
            document.addEventListener(name, activity, { passive: true });
        });
        button.addEventListener('click', function (event) {
            if (event.isTrusted) sync(Date.now() < deadline);
        });
        window.addEventListener('storage', function (event) {
            if (event.key !== storageKey || !event.newValue) return;
            try {
                const data = JSON.parse(event.newValue);
                if (data.expired) leave(false);
                else if (Number.isFinite(data.deadline)) { deadline = Math.max(deadline, data.deadline); render(); }
            } catch (_) { /* ignora mensagem inválida */ }
        });
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') sync(false);
        });
        window.addEventListener('pageshow', function (event) { if (event.persisted) sync(false); });
        setInterval(function () {
            render();
            if (leaving || busy) return;
            if (Date.now() >= deadline) sync(false);
            else if (pendingActivity && Date.now() - lastRequest >= 10000) sync(true);
            else if (Date.now() - lastStatus >= 30000) sync(false);
        }, 1000);
        render();
        sync(false);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
