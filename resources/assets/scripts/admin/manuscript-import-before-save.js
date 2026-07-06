(function (window, document) {
    'use strict';

    var config = window.chaoManuscriptImportConfig || null;

    if (!config || !document || typeof window.fetch !== 'function') {
        return;
    }

    var importMetaboxSelector = '#manuscript-import';
    var pagesMetaboxSelector = '#manuscript-import-pages';
    var importButtonSelector = '#manuscript-import .button';
    var liveActionAttribute = 'data-chao-manuscript-import-live';
    var liveButtonAttribute = 'data-chao-manuscript-import-button';
    var fieldSelectors = [
        '[name="acf[field_manuscript_source_subfolder]"]',
        '[name="manuscript_source_subfolder"]'
    ];
    var isSubmitting = false;

    function findField() {
        var index;

        for (index = 0; index < fieldSelectors.length; index += 1) {
            var field = document.querySelector(fieldSelectors[index]);

            if (field) {
                return field;
            }
        }

        return null;
    }

    function readSubfolder() {
        var field = findField();

        return field ? String(field.value || '').trim() : '';
    }

    function getMetaboxInside(selector) {
        return document.querySelector(selector + ' .inside');
    }

    function getImportButton() {
        return document.querySelector(importButtonSelector);
    }

    function matchesImportField(field) {
        return !!(
            field &&
            field.matches &&
            (field.matches(fieldSelectors[0]) || field.matches(fieldSelectors[1]))
        );
    }

    function updateHiddenPostId(postId) {
        var fields = document.querySelectorAll('[name="post_ID"]');

        Array.prototype.forEach.call(fields, function (field) {
            field.value = String(postId);
        });
    }

    function updateEditorUrl(editUrl) {
        if (!editUrl || !window.history || typeof window.history.replaceState !== 'function') {
            return;
        }

        window.history.replaceState({}, '', editUrl);
    }

    function buildLiveActionMarkup(subfolder) {
        return '' +
            '<div ' + liveActionAttribute + '="1">' +
                '<p><strong>Subpasta fonte do manuscrito:</strong> ' + escapeHtml(subfolder) + '</p>' +
                '<p><a href="#" class="button button-secondary" ' + liveButtonAttribute + '="1">Importar da subpasta</a></p>' +
            '</div>';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function syncLiveAction() {
        var inside = getMetaboxInside(importMetaboxSelector);
        var subfolder = readSubfolder();
        var liveAction = inside ? inside.querySelector('[' + liveActionAttribute + ']') : null;
        var existingButton = getImportButton();

        if (!inside) {
            return;
        }

        if (subfolder === '') {
            if (liveAction) {
                liveAction.remove();
            }

            return;
        }

        if (existingButton && !liveAction) {
            return;
        }

        if (!liveAction) {
            inside.insertAdjacentHTML('beforeend', buildLiveActionMarkup(subfolder));
            return;
        }

        liveAction.outerHTML = buildLiveActionMarkup(subfolder);
    }

    function serialize(data) {
        var params = new URLSearchParams();

        Object.keys(data).forEach(function (key) {
            if (data[key] === undefined || data[key] === null || data[key] === '') {
                return;
            }

            params.append(key, data[key]);
        });

        return params.toString();
    }

    function postRequest(url, data) {
        return window.fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json'
            },
            body: serialize(data)
        }).then(function (response) {
            return response.text().then(function (text) {
                var payload = null;

                try {
                    payload = JSON.parse(text);
                } catch (error) {
                    payload = null;
                }

                if (!response.ok) {
                    var requestError = new Error(extractMessage(payload, config.messages.requestFailed));

                    requestError.payload = payload;

                    throw requestError;
                }

                return payload;
            });
        });
    }

    function extractMessage(payload, fallback) {
        if (payload && payload.data && payload.data.message) {
            return payload.data.message;
        }

        return fallback;
    }

    function extractData(payload, fallbackMessage) {
        if (!payload || payload.success !== true || !payload.data) {
            throw new Error(extractMessage(payload, fallbackMessage));
        }

        return payload.data;
    }

    function getEditorPostId() {
        var explicitPostId = parseInt(config.postId, 10);

        if (explicitPostId > 0) {
            return explicitPostId;
        }

        if (!window.wp || !window.wp.data || typeof window.wp.data.select !== 'function') {
            return 0;
        }

        var editorStore = window.wp.data.select('core/editor');

        if (!editorStore || typeof editorStore.getCurrentPostId !== 'function') {
            return 0;
        }

        var editorPostId = parseInt(editorStore.getCurrentPostId(), 10);

        return editorPostId > 0 ? editorPostId : 0;
    }

    function ensureImportContext() {
        var currentPostId = getEditorPostId();
        var importNonce = String(config.importNonce || '');

        if (currentPostId > 0 && importNonce !== '' && parseInt(config.postId, 10) === currentPostId) {
            return Promise.resolve({
                postId: currentPostId,
                importNonce: importNonce
            });
        }

        return postRequest(config.createDraftUrl, {
            action: config.createDraftAction,
            post_id: currentPostId > 0 ? currentPostId : '',
            chao_manuscript_import_nonce: config.createDraftNonce
        }).then(function (payload) {
            var data = extractData(payload, config.messages.requestFailed);
            var nextPostId = parseInt(data.post_id, 10);

            if (!(nextPostId > 0) || !data.import_nonce) {
                throw new Error(config.messages.requestFailed);
            }

            config.postId = nextPostId;
            config.importNonce = String(data.import_nonce);

            updateHiddenPostId(nextPostId);
            updateEditorUrl(data.edit_url || '');

            return {
                postId: nextPostId,
                importNonce: config.importNonce
            };
        });
    }

    function replaceMetaboxHtml(selector, html) {
        var inside = getMetaboxInside(selector);

        if (!inside || typeof html !== 'string' || html === '') {
            return;
        }

        inside.innerHTML = html;
    }

    function applyResponseFragments(data) {
        if (!data) {
            return;
        }

        replaceMetaboxHtml(importMetaboxSelector, data.metabox || '');
        replaceMetaboxHtml(pagesMetaboxSelector, data.pages_metabox || '');
        syncLiveAction();
    }

    function setButtonBusy(button, isBusy) {
        if (!button) {
            return;
        }

        if (!button.hasAttribute('data-chao-original-label')) {
            button.setAttribute('data-chao-original-label', button.textContent);
        }

        button.setAttribute('aria-disabled', isBusy ? 'true' : 'false');
        button.classList.toggle('disabled', isBusy);

        if (button.tagName === 'BUTTON') {
            button.disabled = isBusy;
        }

        button.textContent = isBusy ? 'Importando...' : button.getAttribute('data-chao-original-label');
    }

    function handleImportClick(button) {
        var subfolder = readSubfolder();

        if (subfolder === '') {
            window.alert(config.messages.missingSubfolder);
            return;
        }

        if (isSubmitting) {
            return;
        }

        isSubmitting = true;
        setButtonBusy(button, true);

        ensureImportContext()
            .then(function (context) {
                return postRequest(config.importUrl, {
                    action: config.importAction,
                    post_id: context.postId,
                    subfolder: subfolder,
                    response_format: 'json',
                    chao_manuscript_import_nonce: context.importNonce
                });
            })
            .then(function (payload) {
                applyResponseFragments(extractData(payload, config.messages.requestFailed));
            })
            .catch(function (error) {
                applyResponseFragments(error && error.payload ? error.payload.data : null);
                window.alert(error && error.message ? error.message : config.messages.requestFailed);
            })
            .finally(function () {
                isSubmitting = false;
                setButtonBusy(button, false);
            });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest(importButtonSelector);

        if (!button) {
            return;
        }

        event.preventDefault();
        handleImportClick(button);
    });

    document.addEventListener('input', function (event) {
        var field = event.target;

        if (matchesImportField(field)) {
            syncLiveAction();
        }
    });

    syncLiveAction();
})(window, document);
