<?php

namespace App\Manuscripts;

class Admin
{
    public static function register()
    {
        add_action('add_meta_boxes', [static::class, 'addMetabox']);
        add_action('admin_post_chao_import_manuscript_zip', [static::class, 'importZip']);
        add_action('admin_notices', [static::class, 'renderAdminNotice']);
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
        $zipFile = static::resolveZipAttachment($post->ID);
        $snapshot = get_post_meta($post->ID, 'chao_manuscript_import_last_snapshot', true);
        $lastError = get_post_meta($post->ID, 'chao_manuscript_import_last_error', true);
        $lastErrors = get_post_meta($post->ID, 'chao_manuscript_import_last_errors', true);

        echo '<p>' . esc_html__('Use este painel para importar o pacote ZIP do manuscrito salvo no campo dedicado do ACF.', 'chao') . '</p>';

        if (!empty($zipFile['id']) || !empty($zipFile['path'])) {
            $importUrl = static::buildImportUrl($post->ID, wp_create_nonce('chao_import_manuscript_zip_' . $post->ID));

            echo '<p><strong>' . esc_html__('Pacote ZIP do manuscrito:', 'chao') . '</strong> ';

            if (!empty($zipFile['url'])) {
                echo '<a href="' . esc_url($zipFile['url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($zipFile['filename']) . '</a>';
            } else {
                echo esc_html($zipFile['filename']);
            }

            echo '</p>';
            echo '<p><a class="button button-secondary" href="' . esc_url($importUrl) . '">' . esc_html__('Importar pacote ZIP', 'chao') . '</a></p>';
        } else {
            echo '<p>' . esc_html__('Envie um pacote ZIP no campo do manuscrito para habilitar a importacao.', 'chao') . '</p>';
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
            echo '<p><strong>' . esc_html__('Arquivo ZIP:', 'chao') . '</strong> ' . esc_html($snapshot['zip_name'] ?? '') . '<br>';
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
            echo '<p>' . esc_html__('Importe um pacote ZIP do manuscrito para gerar a lista de paginas importadas.', 'chao') . '</p>';
            return;
        }

        echo '<p>' . esc_html__('Resumo compacto das paginas importadas do pacote ZIP para este manuscrito.', 'chao') . '</p>';
        echo '<p>';
        echo esc_html__('Paginas importadas:', 'chao') . ' ' . esc_html((string) count($rows)) . '<br>';
        echo esc_html__('Imagens sem XML correspondente:', 'chao') . ' ' . esc_html((string) $summary['images_without_xml']) . '<br>';
        echo esc_html__('XML sem imagem correspondente:', 'chao') . ' ' . esc_html((string) $summary['xml_without_image']) . '</p>';
        echo '<div style="overflow-x: auto;">';
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Pagina', 'chao') . '</th>';
        echo '<th>' . esc_html__('Arquivo', 'chao') . '</th>';
        echo '<th>' . esc_html__('Status', 'chao') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $row['page_number']) . '</td>';
            echo '<td><strong>' . esc_html($row['filename']) . '</strong></td>';
            echo '<td>' . esc_html($row['import_status']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    protected static function buildImportIssueSummary(array $snapshot, array $lastErrors = [])
    {
        $summary = [
            'images_without_xml' => 0,
            'xml_without_image' => 0,
        ];

        $errorMessages = array_merge(
            array_values((array) ($snapshot['errors'] ?? [])),
            array_values($lastErrors)
        );

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

    public static function importZip()
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
            !wp_verify_nonce($nonce, 'chao_import_manuscript_zip_' . $postId)
        ) {
            if (function_exists('wp_die')) {
                wp_die(__('Solicitacao invalida.', 'chao'));
            }

            exit(1);
        }

        if (!current_user_can('edit_post', $postId)) {
            if (function_exists('wp_die')) {
                wp_die(__('Voce nao tem permissao para importar este manuscrito.', 'chao'));
            }

            exit(1);
        }

        $zipFile = static::resolveZipAttachment($postId);

        if (empty($zipFile['path']) || !is_readable($zipFile['path'])) {
            $errorMessage = __('Pacote ZIP do manuscrito ausente ou invalido.', 'chao');
            update_post_meta($postId, 'chao_manuscript_import_last_error', $errorMessage);
            update_post_meta($postId, 'chao_manuscript_import_last_errors', [$errorMessage]);
            static::redirectBack($postId, 'missing_zip', 'import');
            return;
        }

        try {
            $zipFilename = !empty($zipFile['filename']) ? (string) $zipFile['filename'] : basename((string) $zipFile['path']);
            $zipSlug = static::sanitizeKey((string) pathinfo($zipFilename, PATHINFO_FILENAME));
            $imageOutputDir = dirname((string) $zipFile['path']) . DIRECTORY_SEPARATOR . 'manuscript-imports' . DIRECTORY_SEPARATOR . $postId . DIRECTORY_SEPARATOR . $zipSlug;
            $imageOutputUrl = !empty($zipFile['url'])
                ? rtrim(dirname((string) $zipFile['url']), '/') . '/manuscript-imports/' . rawurlencode((string) $postId) . '/' . rawurlencode($zipSlug)
                : '';
            $result = ZipImport::fromZipPath($zipFile['path'], [
                'image_output_dir' => $imageOutputDir,
                'image_output_url' => $imageOutputUrl,
            ]);

            if (!empty($zipFile['filename'])) {
                $result['zip_name'] = (string) $zipFile['filename'];
            }
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
            'zip_id' => (int) ($zipFile['id'] ?? 0),
            'zip_path' => (string) ($zipFile['path'] ?? ''),
            'zip_url' => (string) ($zipFile['url'] ?? ''),
            'zip_filename' => (string) ($zipFile['filename'] ?? ''),
        ]);
        update_post_meta($postId, 'chao_manuscript_import_last_snapshot', static::buildImportSnapshot($result));
        update_post_meta($postId, 'chao_manuscript_import_last_errors', array_values((array) ($result['errors'] ?? [])));
        static::redirectBack($postId, 'success', 'import');
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

    public static function getNoticeConfig($status)
    {
        $notices = [
            'success' => [
                'type' => 'success',
                'message' => static::translate('Pacote ZIP do manuscrito importado com sucesso.'),
            ],
            'missing_zip' => [
                'type' => 'error',
                'message' => static::translate('Nao foi possivel importar: envie um pacote ZIP do manuscrito.'),
            ],
            'import_unavailable' => [
                'type' => 'warning',
                'message' => static::translate('A importacao do pacote ZIP ainda nao esta disponivel neste ambiente.'),
            ],
            'import_error' => [
                'type' => 'error',
                'message' => static::translate('O pacote ZIP do manuscrito nao pode ser importado. Veja os detalhes no metabox do manuscrito.'),
            ],
        ];

        return isset($notices[$status]) ? $notices[$status] : null;
    }

    public static function buildImportSnapshot(array $result)
    {
        return [
            'zip_name' => isset($result['zip_name']) ? (string) $result['zip_name'] : '',
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

    protected static function resolveZipAttachment($postId)
    {
        $attachmentId = 0;
        $namespacedGetField = __NAMESPACE__ . '\\get_field';
        $namespacedAttachmentUrl = __NAMESPACE__ . '\\wp_get_attachment_url';
        $namespacedAttachedFile = __NAMESPACE__ . '\\get_attached_file';
        $fieldNames = ['manuscript_zip_file'];
        $legacyFieldNames = ['manuscript_master_pdf'];

        foreach (array_merge($fieldNames, $legacyFieldNames) as $fieldName) {
            if (function_exists($namespacedGetField)) {
                $attachmentId = (int) call_user_func($namespacedGetField, $fieldName, $postId, false);
            } elseif (function_exists('get_field')) {
                $attachmentId = (int) get_field($fieldName, $postId, false);
            }

            if ($attachmentId) {
                break;
            }
        }

        if (!$attachmentId) {
            foreach (array_merge($fieldNames, $legacyFieldNames) as $fieldName) {
                $attachmentId = (int) get_post_meta($postId, $fieldName, true);

                if ($attachmentId) {
                    break;
                }
            }
        }

        if (!$attachmentId) {
            $storedSource = get_post_meta($postId, 'chao_manuscript_import_source', true);

            if (is_array($storedSource)) {
                $storedZipId = isset($storedSource['zip_id']) ? (int) $storedSource['zip_id'] : 0;
                $storedZipPath = isset($storedSource['zip_path']) ? (string) $storedSource['zip_path'] : '';
                $storedZipUrl = isset($storedSource['zip_url']) ? (string) $storedSource['zip_url'] : '';
                $storedZipFilename = isset($storedSource['zip_filename']) ? (string) $storedSource['zip_filename'] : '';

                if ($storedZipId || $storedZipPath !== '') {
                    return [
                        'id' => $storedZipId,
                        'url' => $storedZipUrl,
                        'path' => $storedZipPath,
                        'filename' => $storedZipFilename !== '' ? $storedZipFilename : basename($storedZipPath),
                    ];
                }
            }

            return [
                'id' => 0,
                'url' => '',
                'path' => '',
                'filename' => '',
            ];
        }

        if (function_exists($namespacedAttachmentUrl)) {
            $url = (string) call_user_func($namespacedAttachmentUrl, $attachmentId);
        } elseif (function_exists('wp_get_attachment_url')) {
            $url = (string) wp_get_attachment_url($attachmentId);
        } else {
            $url = '';
        }

        if (function_exists($namespacedAttachedFile)) {
            $path = (string) call_user_func($namespacedAttachedFile, $attachmentId);
        } elseif (function_exists('get_attached_file')) {
            $path = (string) get_attached_file($attachmentId);
        } else {
            $path = '';
        }

        return [
            'id' => $attachmentId,
            'url' => $url,
            'path' => $path,
            'filename' => $url !== '' ? basename($url) : ($path !== '' ? basename($path) : ''),
        ];
    }

    public static function buildImportUrl($postId, $nonce, $adminBaseUrl = null)
    {
        $adminBaseUrl = $adminBaseUrl ?: admin_url();
        $basePath = rtrim($adminBaseUrl, '/') . '/admin-post.php';
        $queryArgs = [
            'action' => 'chao_import_manuscript_zip',
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

    protected static function sanitizeKey($value)
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }

        return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $value));
    }

    protected static function wpUnslash($value)
    {
        if (function_exists('wp_unslash')) {
            return wp_unslash($value);
        }

        return stripslashes((string) $value);
    }
}
