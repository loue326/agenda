/**
 * Mini client API du projet.
 *
 * Un seul point d'entrée pour consommer l'API JSON de l'application :
 * - envoie le header Accept: application/json (réponses JSON d'erreur)
 * - envoie le token CSRF pour les requêtes POST / PUT / DELETE
 * - inclut le cookie de session automatiquement (même domaine)
 * - décode le JSON et lève une exception si le statut HTTP est en erreur
 */
window.apiClient = (function () {
    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');

        return meta ? meta.content : '';
    }

    async function request(url, options = {}) {
        const response = await fetch(url, Object.assign({}, options, {
            headers: Object.assign({
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            }, options.headers || {}),
        }));

        // Session expirée : on renvoie l'utilisateur vers la page de connexion.
        if (response.status === 401) {
            window.location.href = '/login';
            return null;
        }

        // 204 No Content : rien à décoder.
        if (response.status === 204) {
            return null;
        }

        const payload = await response.json().catch(function () {
            return null;
        });

        if (!response.ok) {
            const errors = payload && payload.errors
                ? Object.values(payload.errors).flat().join(' ')
                : '';
            const message = payload && payload.message
                ? payload.message
                : 'Erreur HTTP ' + response.status;

            const error = new Error(errors || message);
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        return payload;
    }

    return {
        request: request,
        get: function (url) {
            return request(url);
        },
        post: function (url, data) {
            return request(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
        },
        put: function (url, data) {
            return request(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
        },
        delete: function (url) {
            return request(url, { method: 'DELETE' });
        },
    };
})();
