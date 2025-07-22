window.hideLoadingScreen = function () {
    const l = document.getElementById("loader");
    if (l) {
        l.style.opacity = "0";
        setTimeout(() => l.remove(), 600);
    }
};
setTimeout(() => {
    if (document.getElementById("loader")) window.hideLoadingScreen();
}, 15000);

window.addEventListener("load", () => {
    const loading = document.getElementById("loader");
    if (loading) {
        const minDisplayTime = 1500;
        const remaining = Math.max(0, minDisplayTime - performance.now());
        setTimeout(() => {
            if (!window.keepLoading) window.hideLoadingScreen();
        }, remaining);
        setTimeout(() => {
            if (document.getElementById("loader")) window.hideLoadingScreen();
        }, 10000);
    }
});

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('input[type="email"]').forEach((i) => {
        i.addEventListener('input', () => {
            i.value = i.value.toLowerCase().replace(/\s+/g, '');
        });
    });
});

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("#termo,#termoMobile").forEach((input) => {
        input.addEventListener("input", () => {
            input.value = input.value.toUpperCase();
        });
    });
});


document.addEventListener("DOMContentLoaded", () => {
    document
        .querySelectorAll(".menu-icons-desktop a, .menu__block a")
        .forEach((link) => {
            const href = link.href.replace(/\/$/, "");
            const loc = location.href.replace(/\/$/, "");
            if (href === loc) link.setAttribute("aria-current", "page");
        });
});

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        const warn = document.createElement("div");
        warn.className = "caps-warning mt-1 d-none";
        warn.style.color = "#ec4115";
        warn.style.fontSize = "0.875rem";
        warn.textContent = "Caps Lock ativado";
        input.insertAdjacentElement("afterend", warn);
        const toggle = (e) => {
            const caps = e.getModifierState && e.getModifierState("CapsLock");
            warn.classList.toggle("d-none", !caps);
        };
        input.addEventListener("keydown", toggle);
        input.addEventListener("keyup", toggle);
        input.addEventListener("focus", toggle);
        input.addEventListener("blur", () => warn.classList.add("d-none"));
    });
});

function applyColorScheme(isDark) {
    const meta = document.querySelector('meta[name="color-scheme"]');
    if (meta) meta.setAttribute('content', isDark ? 'dark' : 'light');
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
}

function ensureLgpdPrefix() {
    if (window.LGPDCOOKIES && !LGPDCOOKIES.lgpdPrefix) {
        const el = document.getElementById("lgpd-key");
        if (el) {
            LGPDCOOKIES.lgpdPrefix = el.getAttribute("prefix") || "";
        }
    }
}

function setThemePreference(isDark) {
    if (window.LGPDCOOKIES && typeof LGPDCOOKIES.setCookie === "function") {
        ensureLgpdPrefix();
        LGPDCOOKIES.setCookie("modoEscuro", isDark ? "1" : "0");
    }
    if (typeof localStorage !== "undefined") {
        localStorage.setItem("modoEscuro", isDark ? "1" : "0");
    }
}

function getThemePreference() {
    if (window.LGPDCOOKIES && typeof LGPDCOOKIES.getValueCookie === "function") {
        return LGPDCOOKIES.getValueCookie("modoEscuro");
    } else if (typeof localStorage !== "undefined") {
        return localStorage.getItem("modoEscuro");
    }
    return null;
}

function toggleDarkMode() {
    document.documentElement.classList.toggle("dark-mode");
    const isDark = document.documentElement.classList.contains("dark-mode");
    document.body.classList.toggle("dark-mode", isDark);
    applyColorScheme(isDark);
    document
        .querySelectorAll(".theme-toggle,.theme-toggle-mobile")
        .forEach((btn) => {
            const span = btn.querySelector("span");
            const texto = isDark ? "Modo Claro" : "Modo Escuro";
            if (span) span.innerText = texto;
            btn.title = texto;
        });
    setThemePreference(isDark);
    const logo = document.getElementById("logo");
    if (logo) {
        logo.classList.remove("animate-logo");
        requestAnimationFrame(() => {
            logo.classList.add("animate-logo");
        });
    }
    document.querySelectorAll(".fade-in").forEach((el) => {
        el.classList.remove("fade-in");
        requestAnimationFrame(() => {
            el.classList.add("fade-in");
        });
    });
}

function applyThemePreference() {
    const temaSalvo = getThemePreference();
    const isDark = temaSalvo === "1";
    document.documentElement.classList.toggle("dark-mode", isDark);
    document.body.classList.toggle("dark-mode", isDark);
    applyColorScheme(isDark);
    document
        .querySelectorAll(".theme-toggle,.theme-toggle-mobile")
        .forEach((btn) => {
            const span = btn.querySelector("span");
            const texto = isDark ? "Modo Claro" : "Modo Escuro";
            if (span) span.innerText = texto;
            btn.title = texto;
        });
}

function toggleMenu() {
    const menu = document.getElementById("mobileMenu");
    const hamburger = document.querySelector(".btn__menu");
    const aberto = menu.classList.toggle("active");
    document.body.classList.toggle("no-scroll", aberto);
    if (hamburger) {
        hamburger.setAttribute("aria-expanded", aberto);
        hamburger.classList.toggle("show", aberto);
    }
    const cookieBtn = document.getElementById("lgpdShieldFooter");
    if (cookieBtn) {
        if (aberto) {
            cookieBtn.dataset.prevDisplay = cookieBtn.style.display;
            cookieBtn.style.display = "none";
        } else {
            cookieBtn.style.display = cookieBtn.dataset.prevDisplay || "";
            delete cookieBtn.dataset.prevDisplay;
            handleScroll();
        }
    }
}
["click", "touchstart"].forEach((evt) => {
    document.addEventListener(evt, (e) => {
        const menu = document.getElementById("mobileMenu");
        const hamburger = document.querySelector(".btn__menu");
        const content = menu ? menu.querySelector(".menu__block") : null;
        if (
            menu &&
            menu.classList.contains("active") &&
            content &&
            !content.contains(e.target) &&
            !(hamburger && hamburger.contains(e.target))
        ) {
            menu.classList.remove("active");
            document.body.classList.remove("no-scroll");
            if (hamburger) {
                hamburger.setAttribute("aria-expanded", false);
                hamburger.classList.remove("show");
            }
            const cookieBtn = document.getElementById("lgpdShieldFooter");
            if (cookieBtn) {
                cookieBtn.style.display = cookieBtn.dataset.prevDisplay || "";
                delete cookieBtn.dataset.prevDisplay;
                handleScroll();
            }
        }
    }, { passive: true });
});

applyThemePreference();
window.addEventListener("pageshow", applyThemePreference);
window.addEventListener("load", () => {
    const loading = document.getElementById("loader");
    if (loading && !window.keepLoading) {
        window.hideLoadingScreen();
    }
    const logo = document.getElementById("logo");
    if (logo) {
        logo.classList.add("animate-logo");
    }
    const header = document.querySelector(".header");
    if (header) header.classList.add("fade-in");
    const origem = document.getElementById("titulo-estrutural");
    if (origem) {
        origem.remove();
    }
    const botao = document.getElementById("voltar-topo");
    if (botao) {
        botao.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }
});
const header = document.querySelector(".header");
let cookieHideTimer;

function handleScroll() {
    const currentScroll = window.scrollY;
    if (header) {
        if (currentScroll > 100) {
            header.classList.add("scrolled");
        } else {
            header.classList.remove("scrolled");
        }
    }
    const botao = document.getElementById("voltar-topo");
    if (botao) {
        botao.style.display = currentScroll > 100 ? "block" : "none";
    }
    const cookieButton = document.getElementById("lgpdShieldFooter");
    if (cookieButton) {
        const hasConsent = window.LGPDCOOKIES && LGPDCOOKIES.cookiesConsent !== '';
        if (!hasConsent) {
            cookieButton.style.display = "none";
        } else {
            cookieButton.style.display = currentScroll > 100 ? "none" : "flex";
        }
    }
}
window.addEventListener("scroll", handleScroll, { passive: true });
document.addEventListener("DOMContentLoaded", () => {
    const botao = document.getElementById("voltar-topo");
    if (botao) {
        botao.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }
    handleScroll();
});
document.addEventListener("DOMContentLoaded", () => {
    const codigoRegex = /^[A-Z]{2,4}\.[0-9]{8}$/;
    const referenciaRegex = /^[0-9A-Z]{1,5}(\.[0-9A-Z]{1,5}){2,}$/;
    const isMobile = window.matchMedia("(max-width:768px)").matches;
    function validarPesquisaProduto(valor) {
        if (codigoRegex.test(valor)) return "codigo";
        if (referenciaRegex.test(valor)) return "referencia";
        return null;
    }
    function tratarBuscaProduto(input) {
        const valor = input.value.trim();
        const somentePermitidosRegex = /^[A-Z0-9.]+$/i;
        if (valor && !somentePermitidosRegex.test(valor)) {
            input.classList.add("campo-invalido");
            exibirAlerta(
                "❌ Não use espaços ou caracteres especiais. Use apenas letras,números e pontos.",
            );
            return false;
        }
        const tipo = validarPesquisaProduto(valor);
        if (!tipo) {
            input.classList.add("campo-invalido");
            exibirAlerta(
                "❌ Digite um Código de Produto válido (ex:QU.12345678) ou uma Referência válida (ex:1.Q.050.50.71B14.N.N.N)",
            );
            return false;
        }
        input.classList.remove("campo-invalido");
        console.log(`🔍 Tipo reconhecido:${tipo.toUpperCase()}→ ${valor}`);
        return true;
    }
});
document.addEventListener("DOMContentLoaded", () => {
    const isMobile = window.matchMedia("(max-width:768px)").matches;
    if (isMobile) {
    }
});
function getGrupoUsuario() {
    const token = document.cookie
        .split(";")
        .find((row) => row.startsWith("auth_token="))
        ?.split("=")[1];
    if (!token) return null;
    const [payload] = token.split(".");
    try {
        const dados = JSON.parse(atob(payload));
        if (Date.now() / 1000 > dados.exp) return null;
        return dados.grupo;
    } catch {
        return null;
    }
}
function fecharAlerta() {
    const alerta = document.getElementById("alerta-validacao");
    alerta.classList.remove("alerta-visivel");
    setTimeout(() => (alerta.style.display = "none"), 500);
}
function exibirAlerta(msg) {
    const alerta = document.getElementById("alerta-validacao");
    document.getElementById("alerta-texto").innerHTML = msg;
    alerta.style.display = "block";
    setTimeout(() => {
        alerta.classList.add("alerta-visivel");
        try {
            alerta.focus({ preventScroll: true });
        } catch (e) {
            alerta.focus();
        }
    }, 10);
    setTimeout(() => fecharAlerta(), 30000);
}

let autoScrollAtivo = false;

function smoothScrollParaCentro(el, dur = 250) {
    const inicio = window.pageYOffset;
    const rect = el.getBoundingClientRect();
    const alvo = rect.top + inicio - (window.innerHeight / 2 - rect.height / 2);
    const dist = alvo - inicio;
    const comeco = performance.now();
    function passo(agora) {
        const t = Math.min((agora - comeco) / dur, 1);
        const ease = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        window.scrollTo(0, inicio + dist * ease);
        if (t < 1) requestAnimationFrame(passo);
    }
    requestAnimationFrame(passo);
}

function scrollParaCentro(el) {
    if (!el) return;
    smoothScrollParaCentro(el, 250);
}

function scrollParaCabecalho(el) {
    const header = document.querySelector('.header');
    const headerHeight = header ? header.offsetHeight : 0;
    const destino = el.getBoundingClientRect().top + window.scrollY - headerHeight;
    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
    window.scrollTo({ top: Math.min(destino, maxScroll), behavior: 'smooth' });
}

function scrollParaProximoSelect(atual) {
    const todos = Array.from(document.querySelectorAll("select"));
    const idx = todos.indexOf(atual);
    if (idx === -1) return;
    const scrollAntes = window.scrollY;
    let encontrado = false;
    for (let i = idx + 1; i < todos.length; i++) {
        const proximo = todos[i];
        const estilo = window.getComputedStyle(proximo);
        if (
            estilo.display !== "none" &&
            estilo.visibility !== "hidden" &&
            proximo.offsetParent !== null &&
            (!proximo.value || proximo.value.trim() === "")
        ) {
            encontrado = true;
            smoothScrollParaCentro(proximo, 250);
            setTimeout(() => {
                try {
                    proximo.focus({ preventScroll: true });
                } catch (e) {
                    proximo.focus();
                }
            }, 260);
            break;
        }
    }
    if (!encontrado) {
        autoScrollAtivo = false;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("select").forEach((select) => {
        select.dataset.inicialVazio =
            !select.value || select.value.trim() === "" ? "1" : "0";
        select.addEventListener("change", (e) => {
            if (!e.isTrusted) return;
            const inicialmenteVazio = select.dataset.inicialVazio === "1";
            if (inicialmenteVazio && select.value && !autoScrollAtivo) {
                autoScrollAtivo = true;
            }
            if (autoScrollAtivo && select.value) {
                scrollParaProximoSelect(select);
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", () => {
    document
        .querySelector(".gerar-produto")
        ?.addEventListener("click", function (e) {
            e.preventDefault();
            document.querySelectorAll("select").forEach((select) => {
                select.classList.remove("campo-invalido");
            });
            const selectsVisiveis = Array.from(
                document.querySelectorAll("select"),
            ).filter((select) => {
                const style = window.getComputedStyle(select);
                return (
                    style.display !== "none" &&
                    style.visibility !== "hidden" &&
                    select.offsetParent !== null
                );
            });
            const naoPreenchidos = selectsVisiveis.filter(
                (select) => !select.value || select.value.trim() === "",
            );
            if (naoPreenchidos.length > 0) {
                const campos = naoPreenchidos.map((select) => {
                    const label = document.querySelector(`label[for="${select.id}"]`);
                    return label
                        ? label.innerText.trim().replace(":", "")
                        : select.name || select.id;
                });
                naoPreenchidos.forEach((select, index) => {
                    select.classList.add("campo-invalido");
                    select.addEventListener("change", function handleChange() {
                        if (select.value && select.value.trim() !== "") {
                            select.classList.remove("campo-invalido");
                            select.removeEventListener("change", handleChange);
                        }
                    });
                    if (index === 0) {
                        setTimeout(() => {
                            try {
                                select.focus({ preventScroll: true });
                            } catch (e) {
                                select.focus();
                            }
                        }, 300);
                    }
                });
                const lista = campos.map((c) => `• ${c}`).join("<br>");
                exibirAlerta(
                    "⚠️ Preencha os seguintes campos obrigatórios:<br><br>" + lista,
                );
            } else {
                const evento = new CustomEvent("todosCamposPreenchidos", {
                    detail: { manual: true }
                });
                document.dispatchEvent(evento);
                const botao = document.querySelector('.gerar-produto-wrapper');
                if (botao) scrollParaCentro(botao);
            }
        });
});
if ("serviceWorker" in navigator && (location.protocol === "https:" || location.hostname === "localhost")) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("/Service-Worker.min.js?versao=versao.3.0.1", { updateViaCache: 'none' })
            .catch(err => console.warn('Service worker registration failed:', err));
    });
} else {
    console.warn("Service worker requer HTTPS para funcionar corretamente.");
}

function toggleGrupoDetalhes(grupo) {
    const detalhes = document.getElementById("detalhes" + grupo);
    const seta = document.getElementById("setaToggle" + grupo);
    if (!detalhes) return;
    detalhes.classList.toggle("expandido");
    detalhes.classList.toggle("recolhido");
    if (seta) seta.classList.toggle("aberta");
    if (detalhes.classList.contains("expandido")) {
        scrollParaCabecalho(detalhes);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(el => {
        if (el.getAttribute('title') === null && !el.getAttribute('data-bs-title')) {
            el.setAttribute('data-bs-title', '');
        }
        new bootstrap.Popover(el);
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const helpButtons = document.querySelectorAll('button.help-icon[data-help-text]');
    helpButtons.forEach(btn => {
        if (!btn.innerHTML.trim()) {
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-question-circle text-orange" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/></svg>`;
        }
        btn.type = btn.type || 'button';
        btn.dataset.bsToggle = 'popover';
        if (!btn.dataset.bsTrigger) btn.dataset.bsTrigger = 'manual';
        if (!btn.dataset.bsContainer) btn.dataset.bsContainer = 'body';
        if (!btn.dataset.bsPlacement) btn.dataset.bsPlacement = 'auto';
        if (!btn.dataset.bsHtml) btn.dataset.bsHtml = 'true';
        if (btn.dataset.helpText && !btn.dataset.bsContent) btn.dataset.bsContent = btn.dataset.helpText;
        if (btn.dataset.helpTitle && !btn.dataset.bsTitle) btn.dataset.bsTitle = btn.dataset.helpTitle;
        if (btn.dataset.helpClass && !btn.dataset.bsCustomClass) btn.dataset.bsCustomClass = btn.dataset.helpClass;
        btn.dataset.bsBoundary = "viewport";
        const popover = new bootstrap.Popover(btn);
        btn.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            helpButtons.forEach(other => {
                if (other === btn) return;
                const inst = bootstrap.Popover.getInstance(other);
                if (inst) inst.hide();
            });
            popover.toggle();
        });
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.popover') && !e.target.closest('button.help-icon')) {
            helpButtons.forEach(btn => {
                const instance = bootstrap.Popover.getInstance(btn);
                if (instance) instance.hide();
            });
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            helpButtons.forEach(btn => {
                const instance = bootstrap.Popover.getInstance(btn);
                if (instance) instance.hide();
            });
            return;
        }

        if (e.key === 'ArrowDown') {
            const popoverBody = document.querySelector('.popover.show .popover-body');
            if (popoverBody) {
                e.preventDefault();
                popoverBody.scrollBy({ top: 20 });
            }
        }
    });

    document.addEventListener('click', e => {
        const img = e.target.closest('.popover img');
        if (!img) return;
        e.preventDefault();
        const container = img.closest('.popover-body');
        if (!container) return;
        const imgs = Array.from(container.querySelectorAll('img')).filter(i => i.naturalWidth);
        const index = imgs.indexOf(img);
        const items = imgs.map(i => ({
            src: i.src,
            width: i.naturalWidth || 800,
            height: i.naturalHeight || 600
        }));
        const lb = new PhotoSwipeLightbox({
            dataSource: items,
            pswpModule: PhotoSwipe,
            initialZoomLevel: 1,
            secondaryZoomLevel: 2,
            wheelToZoom: true,
            imageClickAction: 'zoom'
        });
        lb.on('close', () => lb.destroy());
        lb.init();
        lb.loadAndOpen(index);
    });

});

document.addEventListener("DOMContentLoaded", () => {
    const updateSelectTitle = (sel) => {
        const opt = sel.selectedOptions && sel.selectedOptions[0];
        sel.title = opt ? opt.textContent.trim() : '';
    };
    document.querySelectorAll('select').forEach(sel => {
        updateSelectTitle(sel);
        sel.addEventListener('change', () => updateSelectTitle(sel));
    });

    const updateButtonTitle = (btn) => {
        btn.title = btn.textContent.trim();
    };
    document.querySelectorAll('.dropdown-button').forEach(btn => {
        updateButtonTitle(btn);
        new MutationObserver(() => updateButtonTitle(btn))
            .observe(btn, { childList: true, characterData: true, subtree: true });
    });
});

//COOKIES:

window.onload = function () {
    LGPDCOOKIES.onReady();
};

LGPDCOOKIES = {
    baseUrl: (function () {
        const el = document.getElementById('lgpd-key');
        var b = '';
        if (el) b = el.getAttribute('urlBase') || '';
        if (b.startsWith('http')) b = '';
        return b;
    })(),
    lgpdKey: '',
    lgpdPrefix: '',
    cookiesConsent: '',
    localContent: `<body>

    <section id="lgpdCookieFooter">
        <div class="lgpd__cookie__footer">
            <div class="lgpd__cookie__footer__text">
                <p class="title">
                    Nós usamos cookies
                </p>
                    <p class="text">
                        Este site utiliza cookies para lhe oferecer conteúdos personalizados e uma ótima experiência de navegação.
                        Para maiores informações, acesse nossa <a href="https://configurador.redutoresibr.com.br/PoliticaPrivacidade" target="_blank">Política de Privacidade</a> ou acesse as configurações.
                    </p>
            </div>
            <div class="lgpd__cookie__footer__buttons">
                <button id="saveAllFooterLgpdCookies" class="btn__lgpd__cookie btn__lgpd__cookie100" aria-label="Aceitar todos">Aceitar todos</button>
                <button id="openModalLgpdCookies" class=" btn__lgpd__cookie btn__lgpd__cookie100" aria-label="Configurar">Configurar</button>
            </div>
        </div>
    </section>
    <section id="lgpdShieldFooter" style="display: none;">
        <a id="openModalLgpdShield" href="#" onclick="return false;">
                    <div style="display: flex;align-items: center;height: 100%;padding: 0px 15px;">
                                    <img loading="lazy" data-src="/Layout/Imagens/Logotipos/Cookies.svg" alt="Cookies" class="img-fluid" width="88" height="30" style="width: 88px;margin-right: 6px;" src="/Layout/Imagens/Logotipos/Cookies.svg">
                                     <span>Cookies</span>
            </div>
        </a>
    </section>

    <section id="modalLgpdCookies">
        <div id="modalLgpdCookiesContainer">
            <div id="modalLgpdCookiesFechar">
                <svg xmlns="http://www.w3.org/2000/svg" width="1.5em" height="1.5em" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"></path>
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"></path>
                </svg>
            </div>
                <div class="modal__lgpd__cookies__header">
                    <span><a href="https://configurador.redutoresibr.com.br/PoliticaPrivacidade" target="_blank">Política de Privacidade</a></span>
                </div>
            <div class="modal__lgpd__cookies__button__tabs">
                <button class="active" id="modalLgpdCookiesButtonTab1" aria-label="Configurações">Configurações</button>
                <button id="modalLgpdCookiesButtonTab2" aria-label="Cookies">Cookies</button>
                <button id="modalLgpdCookiesButtonTab3" hidden="" style="visibility:hidden" aria-label="Política de cookies">Política de cookies</button>
            </div>
            <div class="modal__lgpd__cookies__body">
                <div id="modalLgpdCookiesConfig">
                    <p class="info">Gostaríamos da sua permissão para utilizar os seus dados para os seguintes fins:</p>

                    <div id="modalLgpdCookiesConfigText">
                        <p class="title">Necessário</p>
                        <div class="modal__lgpd__cookies__text__div">
                            <p class="text">
                                Estes cookies são necessários para o bom funcionamento do nosso site e não podem ser desligados no nosso sistema.
                            </p>
                            <button id="modalLgpdCheckboxNecessary" class="checkboxModalLgpdCookies on" role="checkbox" aria-checked="true"></button>
                        </div>
                    </div>

                        <div id="modalLgpdCookiesConfigText">
                            <p class="title">Desempenho</p>
                            <div class="modal__lgpd__cookies__text__div">
                                <p class="text">
                                    Utilizamos estes cookies para fornecer informações estatísticas sobre o nosso site - eles são usados para medir e melhorar o desempenho.
                                </p>
                                <button id="modalLgpdCheckboxPerformance" class="checkboxModalLgpdCookies on" role="checkbox" aria-checked="true"></button>
                            </div>
                        </div>



                </div>
                <div id="modalLgpdCookiesCookie">
                    <div class="modal__lgpd__cookies__tab__text">
                                    <h5>Desempenho</h5>
                                <div class="item">
                                    <p class="title"><strong>Cookie - GA</strong></p>
                                    <p>

                                    </p>
                                </div>

                    </div>
                </div>
                <div id="modalLgpdCookiesPC">

                </div>
            </div>

            <div class="modal__lgpd__cookies__button__footer">
                <button id="saveAllModalLgpdCookies" class="btn btn-success btn__lgpd__cookie btn__lgpd__cookie50" aria-label="Aceitar todos">Aceitar todos</button>
                <button id="saveConfigModalLgpdCookies" class="btn__lgpd__cookie btn__lgpd__cookie50" aria-label="Salvar configurações">Salvar configurações</button>
            </div>

        </div>
    </section>
</body><script type="text/javascript">
        LGPDINJECT = {
            onReady: function () {
                LGPDINJECT.includeModalEvents();
                LGPDINJECT.includeEvents();
                LGPDINJECT.includeScripts();
            },
            includeModalEvents: function () {
                var open = document.getElementById('openModalLgpdCookies');
                var openShield = document.getElementById('openModalLgpdShield');
                var close = document.getElementById('modalLgpdCookiesFechar');
                var fade = document.getElementById('modalLgpdCookies');
                open.onclick = function () { fade.style.display = "flex" };
                openShield.onclick = function () { fade.style.display = "flex" };
                close.onclick = function () { fade.style.display = "none" };

                var performance = document.getElementById('modalLgpdCheckboxPerformance');
                if (performance)
                    performance.onclick = function () { LGPDINJECT.modalLgpdCookiesConfigBtnCheckbox(this) };

                var functional = document.getElementById('modalLgpdCheckboxFunctional');
                if (functional)
                    functional.onclick = function () { LGPDINJECT.modalLgpdCookiesConfigBtnCheckbox(this) };

                var advertising = document.getElementById('modalLgpdCheckboxAdvertising');
                if (advertising)
                    advertising.onclick = function () { LGPDINJECT.modalLgpdCookiesConfigBtnCheckbox(this) };

                var tab1 = document.getElementById('modalLgpdCookiesConfig');
                var tab2 = document.getElementById('modalLgpdCookiesCookie');
                var tab3 = document.getElementById('modalLgpdCookiesPC');

                document.getElementById("modalLgpdCookiesButtonTab1").onclick = function () {
                    document.getElementById("modalLgpdCookiesButtonTab1").classList.add('active');
                    document.getElementById("modalLgpdCookiesButtonTab2").classList.remove('active');
                    document.getElementById("modalLgpdCookiesButtonTab3").classList.remove('active');
                    tab1.style.display = "block";
                    tab3.style.display = "none";
                    tab2.style.display = "none";
                };

                document.getElementById("modalLgpdCookiesButtonTab2").onclick = function () {
                    document.getElementById("modalLgpdCookiesButtonTab1").classList.remove('active');
                    document.getElementById("modalLgpdCookiesButtonTab2").classList.add('active');
                    document.getElementById("modalLgpdCookiesButtonTab3").classList.remove('active');
                    tab1.style.display = "none";
                    tab3.style.display = "none";
                    tab2.style.display = "block";
                };

                document.getElementById("modalLgpdCookiesButtonTab3").onclick = function () {
                    document.getElementById("modalLgpdCookiesButtonTab1").classList.remove('active');
                    document.getElementById("modalLgpdCookiesButtonTab2").classList.remove('active');
                    document.getElementById("modalLgpdCookiesButtonTab3").classList.add('active');
                    tab1.style.display = "none";
                    tab2.style.display = "none";
                    tab3.style.display = "block";
                };
            },
            includeEvents: function () {
                document.getElementById("saveAllFooterLgpdCookies").onclick = function () {
                    LGPDCOOKIES.saveConsent(true);
                };

                document.getElementById("saveAllModalLgpdCookies").onclick = function () {
                    LGPDCOOKIES.saveConsent(true);
                };

                document.getElementById("saveConfigModalLgpdCookies").onclick = function () {
                    LGPDCOOKIES.saveConsent(false);
                };
            },
            modalLgpdCookiesConfigBtnCheckbox: function (element) {
                if (element.classList.contains('on')) {
                    element.classList.remove('on');
                } else {
                    element.classList.add('on');
                }
            },
            includeScripts: function () {
                            
                var d = document;


            },
            addScript: function (head, attributes, text, callback) {
                var s = document.createElement('script');
                for (var attr in attributes) {
                    if (attributes[attr] != null) {
                        s.setAttribute(attr, attributes[attr]);
                    }
                }

                s.innerHTML = text;
                s.onload = callback;
                if (head) {
                    document.head.appendChild(s);
                } else {
                    document.body.appendChild(s);
                }
            },
        };

        (function () {
            LGPDINJECT.onReady();
        })();
</script>`,
    onReady: function () {
        const keyEl = document.getElementById('lgpd-key');
        LGPDCOOKIES.lgpdPrefix = keyEl ? keyEl.getAttribute('prefix') : '';
        LGPDCOOKIES.cookiesConsent = LGPDCOOKIES.getValueCookie('lgpd-cookies-consent');

        LGPDCOOKIES.writeContent(LGPDCOOKIES.localContent, '');
    },
    sendCookies: function () {
        const modelSweep = [];
        if (document.cookie.length > 0) {
            const allCookies = document.cookie.split(/;\s*/);

            allCookies.forEach(function (cookie) {
                const cookieParts = cookie.split('=');
                modelSweep.push({
                    'Name': cookieParts[0],
                    'Type': 'Cookie'
                });
            });
        }

        if (typeof (Storage) !== "undefined") {
            for (var i = 0; i < localStorage.length; i++) {
                modelSweep.push({
                    'Name': localStorage.key(i),
                    'Type': 'LocalStorage'
                });
            }

            for (var i = 0; i < sessionStorage.length; i++) {
                modelSweep.push({
                    'Name': sessionStorage.key(i),
                    'Type': 'SessionStorage'
                });
            }
        }

        if (modelSweep.length > 0) {
            const modelSendCookies = {
                'Cookies': modelSweep,
                'Location': window.location.origin
            };

            LGPDCOOKIES.sendPost(
                'Cookie/SendCookies',
                JSON.stringify(modelSendCookies),
                null);
        }
    },
    getConfig: function (lastUpdateDate) {
        const modelGetConfig = {
            'LastUpdateDate': lastUpdateDate == '' ? null : lastUpdateDate,
            'Location': window.location.origin
        };

        LGPDCOOKIES.sendPost(
            'Config/ObterConfig',
            JSON.stringify(modelGetConfig),
            function (response) {
                const config = JSON.parse(response);
                if (!config.Error) {
                    if (config.GetDataLocally && typeof (Storage) !== "undefined") {
                        console.log('search locally');
                        LGPDCOOKIES.writeContent(decodeURIComponent(localStorage.getItem(LGPDCOOKIES.lgpdPrefix + '-' + 'lgpd-cookies-content')), config.LastUpdateDate)
                    }
                    else {
                        console.log('search server')
                        LGPDCOOKIES.getWriteContent(config.LastUpdateDate);
                    }

                    if (config.Sweep)
                        setTimeout(function () { LGPDCOOKIES.sendCookies(); }, 10000);
                }
            });
    },
    getWriteContent: function (lastUpdateDate) {
        var consentParse = LGPDCOOKIES.cookiesConsent == '' ? null : JSON.parse(decodeURIComponent(LGPDCOOKIES.cookiesConsent));
        const modelGetContent = {
            'Necessary': consentParse == null ? true : consentParse.Necessary,
            'Functional': consentParse == null ? false : consentParse.Functional,
            'Advertising': consentParse == null ? false : consentParse.Advertising,
            'Performance': consentParse == null ? false : consentParse.Performance,
            'Location': window.location.origin
        };

        LGPDCOOKIES.sendPost(
            'Content/Content',
            JSON.stringify(modelGetContent),
            function (response) {
                if (typeof (Storage) !== "undefined") {
                    localStorage.setItem(LGPDCOOKIES.lgpdPrefix + '-' + 'lgpd-cookies-content', encodeURIComponent(response));
                }
                LGPDCOOKIES.writeContent(response, lastUpdateDate);
            });
    },
    writeContent: function (content, lastUpdateDate) {
        var d = document;

        var c = d.createElement("div");
        c.setAttribute('id', 'lgpdDivFooter');
        c.innerHTML = /<body.*?>([\s\S]*)<\/body>/.exec(content)[1];
        d.body.appendChild(c);

        var s = d.createElement("script");
        s.type = 'text/javascript';
        s.innerHTML = /<script.*?>([\s\S]*)<\/script>/.exec(content)[1];

        d.body.appendChild(s);

        window.requestAnimationFrame(
            function () {
                LGPDCOOKIES.setCookie('lgpd-cookies-last-update-date', lastUpdateDate);
                LGPDCOOKIES.openFooter();
                LGPDCOOKIES.applyConsentState();
                handleScroll();
            });
    },
    getValueCookie: function (cname) {
        var cname = LGPDCOOKIES.lgpdPrefix + '-' + cname;
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    },
    setCookie: function (cname, cvalue) {
        var cname = LGPDCOOKIES.lgpdPrefix + '-' + cname;
        var d = new Date();
        d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    },
    sendPost: function (endpoint, request, callback) {
        var url = LGPDCOOKIES.baseUrl + endpoint, xmlhttp;
        if ("XMLHttpRequest" in window) xmlhttp = new XMLHttpRequest();
        if ("ActiveXObject" in window) xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        xmlhttp.open('POST', url, true);
        xmlhttp.setRequestHeader("Authorization", LGPDCOOKIES.lgpdKey);
        xmlhttp.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && callback != null) {
                callback(xmlhttp.responseText);
            }
        };
        xmlhttp.send(request);
    },

    openFooter: function () {
        var footer = document.getElementById('lgpdCookieFooter');
        var shield = document.getElementById('lgpdShieldFooter');
        if (LGPDCOOKIES.cookiesConsent == '') {
            if (footer) footer.style.display = "flex";
            if (shield) shield.style.display = "none";
        } else {
            if (footer) footer.style.display = "none";
            if (shield) shield.style.disp
        }
    },
    applyConsentState: function () {
        if (LGPDCOOKIES.cookiesConsent == '') return;
        var consent = {};
        try {
            consent = JSON.parse(decodeURIComponent(LGPDCOOKIES.cookiesConsent));
        } catch (e) { return; }

        var map = {
            'Functional': 'modalLgpdCheckboxFunctional',
            'Advertising': 'modalLgpdCheckboxAdvertising',
            'Performance': 'modalLgpdCheckboxPerformance'
        };
        for (var key in map) {
            var el = document.getElementById(map[key]);
            if (!el) continue;
            if (consent[key]) {
                el.classList.add('on');
                el.setAttribute('aria-checked', 'true');
            } else {
                el.classList.remove('on');
                el.setAttribute('aria-checked', 'false');
            }
        }
    },
    saveConsent: function (all) {
        var modelSetConsent = {};

        if (all) {
            modelSetConsent = {
                'Necessary': true,
                'Functional': true,
                'Advertising': true,
                'Performance': true,
                'Location': window.location.origin
            };
        } else {
            modelSetConsent = {
                'Necessary': true,
                'Functional': document.getElementById("modalLgpdCheckboxFunctional") ? document.getElementById("modalLgpdCheckboxFunctional").classList.contains('on') : false,
                'Advertising': document.getElementById("modalLgpdCheckboxAdvertising") ? document.getElementById("modalLgpdCheckboxAdvertising").classList.contains('on') : false,
                'Performance': document.getElementById("modalLgpdCheckboxPerformance") ? document.getElementById("modalLgpdCheckboxPerformance").classList.contains('on') : false,
                'Location': window.location.origin
            };
        }

        LGPDCOOKIES.setCookie('lgpd-cookies-consent', encodeURIComponent(JSON.stringify(modelSetConsent)));
        LGPDCOOKIES.cookiesConsent = LGPDCOOKIES.getValueCookie('lgpd-cookies-consent');
        LGPDCOOKIES.setCookie('lgpd-cookies-last-update-date', '');

        location.reload();
    }

}
window.addEventListener('error', (e) => {
    try {
        fetch('/LogsErros/RegistrarErroJS.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                msg: e.message,
                url: e.filename,
                linha: e.lineno,
                coluna: e.colno,
                stack: e.error ? e.error.stack : ''
            })
        });
        if (window.gtag) {
            gtag('event', 'js_error', {
                event_category: 'Erro',
                event_label: e.message || ''
            });
        }
    } catch { }
});

window.addEventListener('unhandledrejection', (e) => {
    try {
        const r = e.reason || {};
        fetch('/LogsErros/RegistrarErroJS.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                msg: r.message || String(r),
                stack: r.stack || ''
            })
        });
        if (window.gtag) {
            gtag('event', 'js_error', {
                event_category: 'Erro',
                event_label: r.message || String(r)
            });
        }
    } catch { }
});