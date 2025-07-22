const APP_VERSION = "versao.2.0.43";

function getSavedTheme() {
    try {
        if (window.LGPDCOOKIES && typeof LGPDCOOKIES.getValueCookie === "function") {
            return LGPDCOOKIES.getValueCookie("modoEscuro");
        }
    } catch { }

    try {
        return localStorage.getItem("modoEscuro");
    } catch { }

    return null;
}

function applySavedTheme(doc) {
    const pref = getSavedTheme();
    doc = doc || document;
    const meta = doc.querySelector('meta[name="color-scheme"]');

    if (pref === "1") {
        doc.documentElement.classList.add("dark-mode");
        if (doc.body) doc.body.classList.add("dark-mode");
        if (meta) meta.setAttribute("content", "dark");
    } else {
        doc.documentElement.classList.remove("dark-mode");
        if (doc.body) doc.body.classList.remove("dark-mode");
        if (meta) meta.setAttribute("content", "light");
    }
}

window.addEventListener("pageshow", () => applySavedTheme(document));
if (window.scriptSources = window.scriptSources || [], applySavedTheme(document), window.csrfSetup === undefined) {
    window.csrfSetup = true;
    window.csrfToken = null;

    let e = fetch("/GetCSRF.php")
        .then(e => e.json())
        .then(e => {
            window.csrfToken = e.token;
        })
        .catch(() => { });

    const t = window.fetch.bind(window);

    window.fetch = async function (o, n = {}) {
        if (n && (n.method || "GET").toUpperCase() === "POST") {
            n.headers = n.headers || {};
            if (!window.csrfToken) {
                try {
                    await e;
                } catch { }
            }

            if (window.csrfToken) {
                if (n.headers instanceof Headers) {
                    n.headers.append("X-CSRF-Token", window.csrfToken);
                } else {
                    n.headers["X-CSRF-Token"] = window.csrfToken;
                }
            }
        }
        try {
            return await t(o, n);
        } catch (err) {
            redirecionarSeOffline();
            throw err;
        }
    };
}

async function clearCachesAndReload() {
    try {
        const e = await caches.keys();
        await Promise.all(e.map(e => caches.delete(e)));
    } finally {
        location.reload();
    }
}

localStorage.getItem("app_version") || localStorage.setItem("app_version", APP_VERSION);

const storedVersion = localStorage.getItem("app_version");

if (storedVersion && storedVersion !== APP_VERSION) {
    localStorage.setItem("app_version", APP_VERSION);
    clearCachesAndReload();
}

const redir = new URLSearchParams(location.search).get("url");
if (redir) location.href = redir;

const OFFLINE_PAGE = "/Paginas/PaginaErros/PaginaErros.html";

function redirecionarSeOffline() {
    if (!navigator.onLine && location.pathname !== OFFLINE_PAGE) {
        try {
            sessionStorage.setItem("offline-redirect", location.href);
        } catch { }
        location.replace(`${OFFLINE_PAGE}?code=offline`);
    }
}

const MAINTENANCE_FILE = "/ModoManutencao.json";

async function verificarManutencao() {
    try {
        const e = await fetch(`${MAINTENANCE_FILE}?v=${APP_VERSION}`, { cache: "no-store" });
        if (e.ok) {
            const t = await e.json();
            if (t && t.maintenance && location.pathname !== OFFLINE_PAGE) {
                location.replace(`${OFFLINE_PAGE}?code=manutencao`);
            }
        }
    } catch { }
}

function mostrarAvisoAtualizacao(callback) {
    if (typeof callback === "function") {
        callback();
    }
}

async function carregarPagina(e, t = true) {
    try {
        const o = fetch(`/Layout/Estrutura/Layout.html?v=${APP_VERSION}`).then(e => e.text());
        const n = fetch(`${e}?v=${APP_VERSION}`).then(e => e.text());

        let a = Promise.resolve("");
        let r = Promise.resolve(null);

        if (t) {
            a = fetch(`/Paginas/Universais/GerarProdutoeInformacoesProduto.html?v=${APP_VERSION}`).then(e => e.text());
            r = fetch(`/Paginas/AreaCliente/AcessoCadastro/Acesso/ValidarSessao.php?v=${APP_VERSION}`)
                .then(e => e.json())
                .catch(() => null);
        }

        let [c, i, s, d] = await Promise.all([o, n, a, r]);
        let l = "";

        if (t && d) {
            const grupo = (d.grupo?.toUpperCase()) || "";
            if (grupo === "OURO" || grupo === "DIAMANTE") {
                l = await fetch(`/Paginas/Universais/EstoqueExterno.html?v=${APP_VERSION}`).then(e => e.text());
            } else if (grupo === "ADMINISTRADOR" || grupo === "INTERNO") {
                l = await fetch(`/Paginas/Universais/EstoqueInterno.html?v=${APP_VERSION}`).then(e => e.text());
            }
        }

        const u = window.scriptSources;
        u.length = 0;

        c = c.replace(/<script\b([^>]*)\bsrc="([^"]+)"([^>]*)><\/script>/gi, (e, t, o, n) => {
            const attrsString = `${t} ${n}`.trim();
            const attrs = {};
            attrsString.replace(/([\w-:]+)(?:=(["'])(.*?)\2)?/g, (e, t, o, n) => {
                attrs[t] = n === undefined ? true : n;
            });
            u.push({ src: o, attrs });
            return "";
        });

        const f = i + s + l;
        const m = (new DOMParser).parseFromString(c, "text/html");

        applySavedTheme(m);

        const h = m.querySelector("footer");
        if (h) {
            h.insertAdjacentHTML("beforebegin", f);
        } else {
            m.body.insertAdjacentHTML("beforeend", f);
        }

        for (const e of Array.from(m.documentElement.attributes)) {
            document.documentElement.setAttribute(e.name, e.value);
        }

        const p = [];

        const v = (target, source) => {
            target.innerHTML = "";
            for (const node of Array.from(source.childNodes)) {
                if (node.tagName === "SCRIPT") {
                    const script = document.createElement("script");
                    for (const attr of Array.from(node.attributes)) {
                        script.setAttribute(attr.name, attr.value);
                    }
                    script.textContent = node.textContent;
                    if (script.src) {
                        p.push(new Promise(resolve => {
                            script.onload = resolve;
                            script.onerror = resolve;
                        }));
                    }
                    target.appendChild(script);
                } else {
                    target.appendChild(node.cloneNode(true));
                }
            }
        };

        v(document.head, m.head);
        v(document.body, m.body);

        const pageDoc = new DOMParser().parseFromString(i, "text/html");
        const pageTitle = pageDoc.querySelector("title");
        if (pageTitle) document.title = pageTitle.textContent;

        for (const meta of pageDoc.head.querySelectorAll("meta[name], meta[property]")) {
            let selector = meta.hasAttribute("name")
                ? `meta[name="${meta.getAttribute("name")}"]`
                : `meta[property="${meta.getAttribute("property")}"]`;
            let existing = document.head.querySelector(selector);
            if (!existing) {
                existing = document.createElement("meta");
                if (meta.hasAttribute("name")) existing.setAttribute("name", meta.getAttribute("name"));
                if (meta.hasAttribute("property")) existing.setAttribute("property", meta.getAttribute("property"));
                document.head.appendChild(existing);
            }
            const content = meta.getAttribute("content");
            if (content !== null) existing.setAttribute("content", content);
        }

        const pageCanonical = pageDoc.head.querySelector('link[rel="canonical"]');
        if (pageCanonical) {
            let link = document.head.querySelector('link[rel="canonical"]');
            if (!link) {
                link = document.createElement('link');
                link.setAttribute('rel', 'canonical');
                document.head.appendChild(link);
            }
            link.setAttribute('href', pageCanonical.getAttribute('href'));
        }

        await Promise.all(p);

        const E = ({ src, attrs }) => new Promise(resolve => {
            const script = document.createElement("script");
            script.src = src;
            if (attrs) {
                for (const [key, value] of Object.entries(attrs)) {
                    if (key !== "src") {
                        if (value === true) {
                            script.setAttribute(key, "");
                        } else {
                            script.setAttribute(key, value);
                        }
                    }
                }
            }
            script.onload = resolve;
            script.onerror = resolve;
            document.head.appendChild(script);
        });

        for (const e of u) {
            await E(e);
        }

        document.dispatchEvent(new Event("DOMContentLoaded"));
        window.dispatchEvent(new Event("load"));

        const titulo = document.getElementById("titulo-estrutural");
        if (titulo) titulo.remove();

    } catch (e) {
        if (navigator.onLine) {
            document.body.innerHTML = `<p style="color:red">⚠️ Erro ao Carregar a Página: ${e.message}</p>`;
        } else {
            location.replace(`${OFFLINE_PAGE}?code=offline`);
        }
    }
}

verificarManutencao();
window.addEventListener("offline", redirecionarSeOffline);
redirecionarSeOffline();

if ("serviceWorker" in navigator && (location.protocol === "https:" || location.hostname === "localhost")) {
    navigator.serviceWorker.register(`/Service-Worker.min.js?versao=${APP_VERSION}`, { updateViaCache: "none" })
        .then(e => {
            if ("periodicSync" in e) {
                e.periodicSync.register("update-content", { minInterval: 86400000 }).catch(() => { });
            }
            if ("sync" in e) {
                e.sync.register("sync-requests").catch(() => { });
            }
        })
        .catch(e => console.warn("Service worker registration failed:", e));

    navigator.serviceWorker.addEventListener("message", e => {
        const t = e.data;
        if (t && (t === "updated" || t.type === "updated")) {
            const e = t.version || APP_VERSION;
            const o = localStorage.getItem("app_version");
            if (e !== o) {
                mostrarAvisoAtualizacao(() => {
                    localStorage.setItem("app_version", e);
                    clearCachesAndReload();
                });
            }
        }
    });
}
