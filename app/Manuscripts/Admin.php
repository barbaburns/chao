<?php

namespace App\Manuscripts;

class Admin
{
    public static function register()
    {
        add_action('add_meta_boxes', [static::class, 'addMetabox']);
        add_action('admin_post_chao_create_manuscript_draft', [static::class, 'createDraftForImport']);
        add_action('admin_post_chao_import_manuscript_source_folder', [static::class, 'importSourceFolder']);
        add_action('admin_notices', [static::class, 'renderAdminNotice']);
        add_action('admin_enqueue_scripts', [static::class, 'enqueueEditorRefreshScript']);
        add_action('admin_footer-post.php', [static::class, 'renderEditorRefreshScript']);
        add_action('admin_footer-post-new.php', [static::class, 'renderEditorRefreshScript']);
    }

    public static function addMetabox()
    {
        add_meta_box(
            'manuscript-import',
            __('Importacao de manuscrito', 'chao'),
            [static::class, 'renderMetabox'],
            'manuscrito',
            'side'
        );

        add_meta_box(
            'manuscript-import-pages',
            __('Paginas importadas', 'chao'),
            [static::class, 'renderPagesMetabox'],
            'manuscrito',
            'normal',
            'default'
        );
    }

    public static function renderMetabox($post)
    {
        $sourceFolder = static::resolveSourceFolder($post->ID);
        $configuredSubfolder = static::readSourceSubfolderField($post->ID);
        $snapshot = get_post_meta($post->ID, 'chao_manuscript_import_last_snapshot', true);
        $lastError = get_post_meta($post->ID, 'chao_manuscript_import_last_error', true);
        $lastErrors = get_post_meta($post->ID, 'chao_manuscript_import_last_errors', true);

        echo '<p>' . esc_html__('Use este painel para importar a pasta fonte do manuscrito usando a subpasta informada no campo dedicado do ACF.', 'chao') . '</p>';

        if (!empty($sourceFolder['subfolder']) && !empty($sourceFolder['path'])) {
            $importUrl = static::buildImportUrl($post->ID, wp_create_nonce('chao_import_manuscript_source_folder_' . $post->ID));

            echo '<p><strong>' . esc_html__('Subpasta fonte do manuscrito:', 'chao') . '</strong> ';
            echo esc_html($sourceFolder['subfolder']);
            echo '</p>';

            echo '<p><a class="button button-secondary" href="' . esc_url($importUrl) . '">' . esc_html__('Importar da subpasta', 'chao') . '</a></p>';
        } else {
            echo '<p>' . esc_html__('Informe uma subpasta fonte valida do manuscrito no campo dedicado do ACF para habilitar a importacao.', 'chao') . '</p>';

            if ($configuredSubfolder !== '') {
                echo '<p><strong>' . esc_html__('Subpasta informada:', 'chao') . '</strong> ' . esc_html($configuredSubfolder) . '</p>';
            }
        }

        if (!empty($lastError)) {
            echo '<p><strong>' . esc_html__('Ultimo erro:', 'chao') . '</strong><br>' . esc_html($lastError) . '</p>';
        }

        if (!empty($lastErrors) && is_array($lastErrors)) {
            echo '<p><strong>' . esc_html__('Avisos da ultima importacao:', 'chao') . '</strong></p>';
            echo '<ul>';

            foreach ($lastErrors as $importWarning) {
                $importWarning = trim((string) $importWarning);

                if ($importWarning === '') {
                    continue;
                }

                echo '<li>' . esc_html($importWarning) . '</li>';
            }

            echo '</ul>';
        }

        if (!empty($snapshot) && is_array($snapshot)) {
            echo '<p><strong>' . esc_html__('Ultima importacao salva:', 'chao') . '</strong></p>';
            echo '<p><strong>' . esc_html__('Pasta fonte:', 'chao') . '</strong> ' . esc_html((string) ($snapshot['source_name'] ?? '')) . '<br>';
            echo '<strong>' . esc_html__('Paginas:', 'chao') . '</strong> ' . esc_html((string) ($snapshot['page_count'] ?? 0)) . '<br>';
            echo '<strong>' . esc_html__('Revisao:', 'chao') . '</strong> <a href="#manuscript-import-pages">' . esc_html__('ver tabela de paginas', 'chao') . '</a></p>';
        }
    }

    public static function renderPagesMetabox($post)
    {
        $snapshot = get_post_meta($post->ID, 'chao_manuscript_import_last_snapshot', true);
        $lastErrors = get_post_meta($post->ID, 'chao_manuscript_import_last_errors', true);
        $rows = static::buildImportedPageRows(
            is_array($snapshot) ? $snapshot : []
        );
        $summary = static::buildImportIssueSummary(
            is_array($snapshot) ? $snapshot : [],
            is_array($lastErrors) ? $lastErrors : []
        );

        if (empty($rows)) {
            echo '<p>' . esc_html__('Importe a pasta fonte do manuscrito para gerar a lista de paginas importadas.', 'chao') . '</p>';
            return;
        }

        echo '<p>' . esc_html__('Resumo compacto das paginas importadas da pasta fonte para este manuscrito.', 'chao') . '</p>';
        echo '<p>';
        echo esc_html__('Paginas importadas:', 'chao') . ' ' . esc_html((string) count($rows)) . '<br>';
        echo esc_html__('Imagens sem XML correspondente:', 'chao') . ' ' . esc_html((string) $summary['images_without_xml']) . '<br>';
        echo esc_html__('XML sem imagem correspondente:', 'chao') . ' ' . esc_html((string) $summary['xml_without_image']) . '</p>';
    }

    protected static function buildImportIssueSummary(array $snapshot, array $lastErrors = [])
    {
        $summary = [
            'images_without_xml' => 0,
            'xml_without_image' => 0,
        ];

        $errorMessages = array_values((array) ($snapshot['errors'] ?? []));

        if (empty($errorMessages)) {
            $errorMessages = array_values($lastErrors);
        }

        $errorMessages = array_values(array_unique($errorMessages));

        foreach ($errorMessages as $errorMessage) {
            $errorMessage = trim((string) $errorMessage);

            if (strpos($errorMessage, 'Imagem sem XML correspondente:') === 0) {
                $summary['images_without_xml'] += 1;
                continue;
            }

            if (strpos($errorMessage, 'XML sem imagem correspondente:') === 0) {
                $summary['xml_without_image'] += 1;
            }
        }

        return $summary;
    }

    public static function importSourceFolder()
    {
        $requestData = static::extractImportRequestData(
            array_merge(
                is_array($_GET) ? $_GET : [],
                is_array($_POST) ? $_POST : []
            )
        );
        $postId = $requestData['post_id'];
        $nonce = $requestData['nonce'];

        if (
            !$postId ||
            !$nonce ||
            !wp_verify_nonce($nonce, 'chao_import_manuscript_source_folder_' . $postId)
        ) {
            static::failRequest(__('Solicitacao invalida.', 'chao'));
        }

        if (!current_user_can('edit_post', $postId)) {
            static::failRequest(__('Voce nao tem permissao para importar este manuscrito.', 'chao'));
        }

        $sourceFolder = static::resolveSourceFolder($postId);

        if (
            empty($sourceFolder['subfolder']) ||
            empty($sourceFolder['path']) ||
            !is_dir($sourceFolder['path']) ||
            !is_readable($sourceFolder['path'])
        ) {
            $errorMessage = __('Pasta fonte do manuscrito ausente ou invalida.', 'chao');
            update_post_meta($postId, 'chao_manuscript_import_last_error', $errorMessage);
            update_post_meta($postId, 'chao_manuscript_import_last_errors', [$errorMessage]);
            static::redirectBack($postId, 'missing_source_folder', 'import');
            return;
        }

        try {
            $result = SourceFolderImport::fromDirectory($sourceFolder['path'], $sourceFolder['url']);
        } catch (\RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
            update_post_meta($postId, 'chao_manuscript_import_last_error', $errorMessage);
            update_post_meta($postId, 'chao_manuscript_import_last_errors', [$errorMessage]);
            static::redirectBack($postId, 'import_error', 'import');
            return;
        }

        update_post_meta($postId, 'chao_manuscript_import_last_error', '');
        delete_post_meta($postId, 'chao_manuscript_page_review');
        update_post_meta($postId, 'chao_manuscript_import_source', [
            'source_subfolder' => (string) ($sourceFolder['subfolder'] ?? ''),
            'source_path' => (string) ($sourceFolder['path'] ?? ''),
            'source_url' => (string) ($sourceFolder['url'] ?? ''),
        ]);
        update_post_meta($postId, 'chao_manuscript_import_last_snapshot', static::buildImportSnapshot($result));
        update_post_meta($postId, 'chao_manuscript_import_last_errors', array_values((array) ($result['errors'] ?? [])));
        static::redirectBack($postId, 'success', 'import');
    }

    public static function createDraftForImport()
    {
        $nonce = isset($_POST['chao_manuscript_import_nonce']) ? (string) $_POST['chao_manuscript_import_nonce'] : '';

        if (!$nonce || !wp_verify_nonce($nonce, 'chao_manuscript_import_create_draft')) {
            wp_send_json_error(['message' => __('Solicitacao invalida.', 'chao')], 403);
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Voce nao tem permissao para criar este manuscrito.', 'chao')], 403);
        }

        $postId = wp_insert_post([
            'post_type' => 'manuscrito',
            'post_status' => 'draft',
            'post_title' => '',
        ], true);

        if (!$postId || is_wp_error($postId)) {
            wp_send_json_error(['message' => __('Nao foi possivel criar o rascunho do manuscrito.', 'chao')], 500);
        }

        wp_send_json_success([
            'post_id' => (int) $postId,
            'edit_url' => static::buildEditUrl($postId),
        ]);
    }

    public static function renderAdminNotice()
    {
        $postId = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';
        $status = isset($_GET['chao_manuscript_import_notice']) ? (string) $_GET['chao_manuscript_import_notice'] : '';

        if (!$postId || $action !== 'edit' || !$status) {
            return;
        }

        $notice = static::getNoticeConfig($status);

        if (!$notice) {
            return;
        }

        echo '<div class="notice notice-' . esc_attr($notice['type']) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
    }

    public static function enqueueEditorRefreshScript($hookSuffix)
    {
        if (!in_array((string) $hookSuffix, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $namespacedGetCurrentScreen = __NAMESPACE__ . '\\get_current_screen';

        if (function_exists($namespacedGetCurrentScreen)) {
            $screen = call_user_func($namespacedGetCurrentScreen);
        } elseif (function_exists('get_current_screen')) {
            $screen = get_current_screen();
        } else {
            return;
        }

        if (!is_object($screen) || (($screen->post_type ?? '') !== 'manuscrito')) {
            return;
        }

        wp_enqueue_script('wp-data');
        wp_enqueue_script('wp-dom-ready');
        wp_add_inline_script('wp-dom-ready', static::editorRefreshScript());
    }

    public static function renderEditorRefreshScript()
    {
        $namespacedGetCurrentScreen = __NAMESPACE__ . '\\get_current_screen';

        if (function_exists($namespacedGetCurrentScreen)) {
            $screen = call_user_func($namespacedGetCurrentScreen);
        } elseif (function_exists('get_current_screen')) {
            $screen = get_current_screen();
        } else {
            return;
        }

        if (!is_object($screen) || (($screen->post_type ?? '') !== 'manuscrito')) {
            return;
        }

        echo '<script>' . static::editorRefreshScript() . '</script>';
    }

    public static function getNoticeConfig($status)
    {
        $notices = [
            'success' => [
                'type' => 'success',
                'message' => static::translate('Pasta fonte do manuscrito importada com sucesso.'),
            ],
            'missing_source_folder' => [
                'type' => 'error',
                'message' => static::translate('Nao foi possivel importar: informe uma subpasta fonte valida do manuscrito.'),
            ],
            'import_unavailable' => [
                'type' => 'warning',
                'message' => static::translate('A importacao da pasta fonte do manuscrito ainda nao esta disponivel neste ambiente.'),
            ],
            'import_error' => [
                'type' => 'error',
                'message' => static::translate('A pasta fonte do manuscrito nao pode ser importada. Veja os detalhes no metabox do manuscrito.'),
            ],
        ];

        return isset($notices[$status]) ? $notices[$status] : null;
    }

    public static function buildImportSnapshot(array $result)
    {
        return [
            'source_name' => isset($result['source_name']) ? (string) $result['source_name'] : '',
            'page_count' => isset($result['page_count']) ? (int) $result['page_count'] : count((array) ($result['pages'] ?? [])),
            'pages' => array_values((array) ($result['pages'] ?? [])),
            'errors' => array_values((array) ($result['errors'] ?? [])),
            'stored_at' => static::currentTimestamp(),
        ];
    }

    public static function buildImportedPageRows(array $snapshot)
    {
        return array_map(function ($page) {
            $pageNumber = isset($page['page_number']) ? (int) $page['page_number'] : 0;

            return [
                'page_number' => $pageNumber,
                'basename' => isset($page['basename']) ? (string) $page['basename'] : '',
                'filename' => isset($page['filename']) ? (string) $page['filename'] : '',
                'image_url' => isset($page['image_url']) ? (string) $page['image_url'] : '',
                'raw_text' => isset($page['raw_text']) ? (string) $page['raw_text'] : '',
                'import_status' => isset($page['import_status']) ? (string) $page['import_status'] : 'pending',
                'highlight_status' => !empty($page['highlight_exact']) ? 'exato' : 'aproximado',
            ];
        }, (array) ($snapshot['pages'] ?? []));
    }

    protected static function redirectBack($postId, $notice = null, $source = 'import')
    {
        $queryArgs = [];

        if ($notice) {
            $queryArgs['chao_manuscript_import_notice'] = $notice;
        }

        wp_safe_redirect(static::buildEditUrl($postId, $queryArgs));
        exit;
    }

    protected static function resolveSourceFolder($postId)
    {
        $configuredSubfolder = static::readSourceSubfolderField($postId);

        if ($configuredSubfolder !== '') {
            try {
                return SourceFolder::resolve($configuredSubfolder);
            } catch (\InvalidArgumentException $exception) {
                return [
                    'subfolder' => $configuredSubfolder,
                    'path' => '',
                    'url' => '',
                ];
            }
        }

        return [
            'subfolder' => '',
            'path' => '',
            'url' => '',
        ];
    }

    protected static function readSourceSubfolderField($postId)
    {
        $namespacedGetField = __NAMESPACE__ . '\\get_field';
        $fieldNames = ['manuscript_source_subfolder'];

        foreach ($fieldNames as $fieldName) {
            if (function_exists($namespacedGetField)) {
                $subfolder = static::normalizeSourceSubfolderValue(call_user_func($namespacedGetField, $fieldName, $postId, false));
            } elseif (function_exists('get_field')) {
                $subfolder = static::normalizeSourceSubfolderValue(get_field($fieldName, $postId, false));
            } else {
                $subfolder = '';
            }

            if ($subfolder !== '') {
                return $subfolder;
            }
        }

        foreach ($fieldNames as $fieldName) {
            $subfolder = static::normalizeSourceSubfolderValue(get_post_meta($postId, $fieldName, true));

            if ($subfolder !== '') {
                return $subfolder;
            }
        }

        return '';
    }

    protected static function normalizeSourceSubfolderValue($value)
    {
        if ($value === null || $value === false || $value === 0) {
            return '';
        }

        return trim((string) $value);
    }

    public static function buildImportUrl($postId, $nonce, $adminBaseUrl = null)
    {
        $adminBaseUrl = $adminBaseUrl ?: admin_url();
        $basePath = rtrim($adminBaseUrl, '/') . '/admin-post.php';
        $queryArgs = [
            'action' => 'chao_import_manuscript_source_folder',
            'post_id' => (int) $postId,
            'chao_manuscript_import_nonce' => $nonce,
        ];

        if (function_exists('add_query_arg')) {
            return add_query_arg($queryArgs, $basePath);
        }

        return $basePath . '?' . http_build_query($queryArgs);
    }

    public static function extractImportRequestData(array $request)
    {
        return [
            'post_id' => isset($request['post_id']) ? (int) $request['post_id'] : 0,
            'nonce' => isset($request['chao_manuscript_import_nonce']) ? (string) $request['chao_manuscript_import_nonce'] : '',
        ];
    }

    public static function buildEditUrl($postId, array $queryArgs = [], $adminBaseUrl = null)
    {
        $adminBaseUrl = $adminBaseUrl ?: admin_url();
        $basePath = rtrim($adminBaseUrl, '/') . '/post.php';
        $queryArgs = array_merge([
            'post' => (int) $postId,
            'action' => 'edit',
        ], $queryArgs);

        if (function_exists('add_query_arg')) {
            return add_query_arg($queryArgs, $basePath);
        }

        return $basePath . '?' . http_build_query($queryArgs);
    }

    protected static function translate($message)
    {
        if (function_exists('__')) {
            return __($message, 'chao');
        }

        return $message;
    }

    protected static function currentTimestamp()
    {
        if (function_exists('current_time')) {
            return (string) current_time('mysql');
        }

        return gmdate('Y-m-d H:i:s');
    }

    protected static function editorRefreshScript()
    {
        return <<<'JS'
(function (wp) {
    if (!wp || !wp.data || !wp.domReady) {
        return;
    }

    wp.domReady(function () {
        var editorStore = wp.data.select('core/editor');

        if (!editorStore || !wp.data.subscribe) {
            return;
        }

        var wasSaving = false;
        var reloadTriggered = false;

        wp.data.subscribe(function () {
            if (reloadTriggered) {
                return;
            }

            var isSaving = typeof editorStore.isSavingPost === 'function' ? editorStore.isSavingPost() : false;
            var isAutosaving = typeof editorStore.isAutosavingPost === 'function' ? editorStore.isAutosavingPost() : false;

            if (isSaving && !isAutosaving) {
                wasSaving = true;
                return;
            }

            if (isSaving || !wasSaving) {
                return;
            }

            wasSaving = false;

            var field = document.querySelector('[name="acf[field_manuscript_source_subfolder]"]');

            if (!field) {
                field = document.querySelector('[name="manuscript_source_subfolder"]');
            }

            var importButton = document.querySelector('#manuscript-import .button');

            if (field && field.value.trim() !== '' && !importButton) {
                reloadTriggered = true;
                window.location.reload();
            }
        });
    });
})(window.wp);
JS;
    }

    protected static function failRequest($message)
    {
        $namespacedWpDie = __NAMESPACE__ . '\\wp_die';

        if (function_exists($namespacedWpDie)) {
            call_user_func($namespacedWpDie, $message);
        }

        if (function_exists('wp_die')) {
            wp_die($message);
        }

        exit(1);
    }

    protected static function wpUnslash($value)
    {
        if (function_exists('wp_unslash')) {
            return wp_unslash($value);
        }

        return stripslashes((string) $value);
    }
}
