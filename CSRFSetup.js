if (window.csrfSetup === undefined) {
    window.csrfSetup = true;
    window.csrfToken = null;

    let tokenPromise = fetch("/GetCSRF.php")
        .then(response => response.json())
        .then(data => {
            window.csrfToken = data.token;
        })
        .catch(() => { });

    const originalFetch = window.fetch.bind(window);

    window.fetch = async function (resource, options = {}) {
        if (options && (options.method || "GET").toUpperCase() === "POST") {
            options.headers = options.headers || {};
            if (!window.csrfToken) {
                try {
                    await tokenPromise;
                } catch { }
            }

            if (window.csrfToken) {
                if (options.headers instanceof Headers) {
                    options.headers.append("X-CSRF-Token", window.csrfToken);
                } else {
                    options.headers["X-CSRF-Token"] = window.csrfToken;
                }
            }
        }
        return originalFetch(resource, options);
    };
}