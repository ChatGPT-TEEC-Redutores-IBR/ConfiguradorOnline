window.dataLayer = window.dataLayer || [];
window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
var gtagLoaded = false;
var pageStartTime = Date.now();
var scrollDepthTracked = false;

function hasAnalyticsConsent() {
    try {
        if (window.LGPDCOOKIES && typeof LGPDCOOKIES.getValueCookie === 'function') {
            var c = LGPDCOOKIES.getValueCookie('lgpd-cookies-consent');
            if (c) {
                var consent = JSON.parse(decodeURIComponent(c));
                return !!consent.Performance;
            }
        }
    } catch (e) { }
    return false;
}

function loadGtag() {
    if (gtagLoaded) return;
    gtagLoaded = true;
    var script = document.createElement('script');
    script.src = 'https://www.googletagmanager.com/gtag/js?id=G-46RENX5RDK';
    script.async = true;
    script.onload = function () {
        gtag('js', new Date());
        gtag('config', 'G-46RENX5RDK', { anonymize_ip: true, transport_type: 'beacon', page_path: location.pathname });
        gtag('event', 'page_loaded', { event_category: 'general', event_label: 'layout' });
    };
    document.head.appendChild(script);
}

function trackPageView(path) {
    if (gtagLoaded) {
        gtag('config', 'G-46RENX5RDK', { page_path: path || location.pathname });
    }
}

function getCurrentLinha() {
    var params = new URLSearchParams(window.location.search);
    for (var _i = 0, _a = Array.from(params.keys()); _i < _a.length; _i++) {
        var k = _a[_i];
        if (k.toUpperCase().endsWith('LN')) {
            return params.get(k) || '';
        }
    }
    var el = document.querySelector('[data-linha]');
    if (el && el.dataset.linha) return el.dataset.linha;
    var input = document.querySelector('input[id$="LN"], select[id$="LN"]');
    return input && input.value ? input.value : '';
}

function sendLoginEventIfNeeded() {
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('login') === 'sucesso') {
            gtag('event', 'login', { event_category: 'Conta' });
            params.delete('login');
            var newQuery = params.toString();
            var newUrl = window.location.pathname + (newQuery ? '?' + newQuery : '') + window.location.hash;
            history.replaceState({}, '', newUrl);
        }
    } catch (e) { }
}

function setupEngagementTracking() {
    window.addEventListener('pagehide', function () {
        if (!gtagLoaded) return;
        var timeSpent = Math.round((Date.now() - pageStartTime) / 1000);
        if (timeSpent > 0) {
            gtag('event', 'tempo_pagina', {
                event_category: 'Engajamento',
                value: timeSpent
            });
        }
    });

    window.addEventListener('scroll', function () {
        if (scrollDepthTracked || !gtagLoaded) return;
        var scrolled = (window.scrollY || document.documentElement.scrollTop) /
            (document.documentElement.scrollHeight - document.documentElement.clientHeight);
        if (scrolled >= 0.75) {
            scrollDepthTracked = true;
            gtag('event', 'scroll_75', { event_category: 'Engajamento' });
        }
    });
}

window.addEventListener('load', function () {
    if (hasAnalyticsConsent()) {
        loadGtag();
        trackPageView();
    }
    setupEventTracking();
    sendLoginEventIfNeeded();
});

function setupEventTracking() {
    var byId = function (id) { return document.getElementById(id); };
    var attach = function (el, event, handler) {
        if (!el || el.dataset.gtagBound) return;
        el.dataset.gtagBound = '1';
        el.addEventListener(event, handler);
    };

    attach(byId('aplicarFiltros'), 'click', function () {
        gtag('event', 'aplicar_filtros', { event_category: 'Filtro' });
    });

    document.querySelectorAll('.botao-home').forEach(function (el) {
        attach(el, 'click', function () {
            var linha = el.dataset.linha || '';
            var label = linha || el.title || el.querySelector('.botao-titulo')?.textContent || '';
            gtag('event', 'acesso_linha_produtos', {
                event_category: 'Navegacao',
                event_label: label.trim()
            });
        });
    });

    document.querySelectorAll('.gerar-produto').forEach(function (el) {
        attach(el, 'click', function () {
            var linha = getCurrentLinha();
            gtag('event', 'gerar_produto', { event_category: 'Produto', event_label: linha });
        });
    });

    attach(byId('toggleProduto'), 'click', function () {
        gtag('event', 'info_produto', {
            event_category: 'Produto',
            event_label: getCurrentLinha()
        });
    });

    attach(byId('toggleEstoque'), 'click', function () {
        gtag('event', 'info_estoque', {
            event_category: 'Produto',
            event_label: getCurrentLinha()
        });
    });

    attach(byId('botaoEnviarDesenho'), 'click', function () {
        gtag('event', 'solicitar_desenho', {
            event_category: 'Produto',
            event_label: getCurrentLinha()
        });
    });

    attach(byId('botaoEnviarCotacao'), 'click', function () {
        gtag('event', 'solicitar_cotacao', {
            event_category: 'Produto',
            event_label: getCurrentLinha()
        });
    });

    attach(byId('btnCompartilhar'), 'click', function () {
        gtag('event', 'compartilhar', {
            event_category: 'Social',
            event_label: getCurrentLinha()
        });
    });

    document.querySelectorAll('a[href="/AreaCliente"]').forEach(function (el) {
        attach(el, 'click', function () {
            gtag('event', 'acesso_area_cliente', {
                event_category: 'Navegacao',
                event_label: getCurrentLinha()
            });
        });
    });


    attach(byId('cadastro'), 'submit', function () {
        gtag('event', 'cadastro', { event_category: 'Conta' });
    });

    attach(document.querySelector('#modal-excluir button.botao-primario'), 'click', function () {
        gtag('event', 'excluir_conta', { event_category: 'Conta' });
    });

    document.body.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (a && a.href.includes('www.redutoresibr.com.br')) {
            gtag('event', 'redirecionar_ibr', { event_category: 'Navegacao', event_label: a.href });
        }
    });

    attach(byId('formBusca'), 'submit', function () {
        gtag('event', 'pesquisar_produto', { event_category: 'Busca' });
    });

    document.querySelectorAll('.theme-toggle, .theme-toggle-mobile').forEach(function (el) {
        attach(el, 'click', function () {
            setTimeout(function () {
                var modo = document.body.classList.contains('dark-mode') ? 'escuro' : 'claro';
                gtag('event', 'alternar_modo_cor', {
                    event_category: 'Personalizacao',
                    event_label: modo
                });
            }, 0);
        });
    });
}
document.addEventListener('DOMContentLoaded', setupEventTracking);

if (navigator.serviceWorker) {
    navigator.serviceWorker.addEventListener('message', function (e) {
        if (e.data && e.data.type === 'offline-request') {
            gtag('event', 'offline_request', {
                event_category: 'ServiceWorker',
                event_label: e.data.url || ''
            });
        }
    });
}