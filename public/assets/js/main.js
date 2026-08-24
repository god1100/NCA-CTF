// NCA Batch 4 CTF — Phase 0 landing page behavior.
// Only calls the health endpoint. No auth, no state, no frameworks.

(function () {
    "use strict";

    const button = document.getElementById("check-health");
    const result = document.getElementById("health-result");

    if (!button || !result) {
        return;
    }

    button.addEventListener("click", async function () {
        result.textContent = "Checking...";

        try {
            const url = (window.NCA_API && typeof window.NCA_API.url === "function")
                ? window.NCA_API.url("/api/v1/health")
                : (window.NCA_CTF_BASE_URL || "/NCA-CTF/public") + "/api/v1/health";
            const response = await fetch(url);
            const body = await response.json();
            result.textContent = JSON.stringify(body, null, 2);
        } catch (err) {
            result.textContent = "Request failed: " + err.message;
        }
    });
})();
