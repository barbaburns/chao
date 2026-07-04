<?php

namespace App\Manuscripts;

class Admin
{
    public static function register()
    {
        add_action('add_meta_boxes', [static::class, 'addMetabox']);
        add_action('admin_post_chao_process_manuscript_pdf', [static::class, 'processPdf']);
        add_action('admin_post_chao_import_manuscript_zip', [static::class, 'importZip']);
        add_action('admin_notices', [static::class, 'renderAdminNotice']);
        add_action('save_post_manuscrito', [static::class, 'savePageReview']);
    }

    public static function addMetabox()
    {
        add_meta_box(
            'manuscript-worker',
            __('Worker local de manuscritos', 'chao'),
            [static::class, 'renderMetabox'],
            'manuscrito',
            'side'
        );

        add_meta_box(
            'manuscript-worker-pages',
            __('Paginas extraidas', 'chao'),
            [static::class, 'renderPagesMetabox'],
            'manuscrito',
            'normal',
            'default'
        );
    }

    public static function renderMetabox($post)
    {
        $resolver = new MasterPdfResolver();
        $masterPdf = $resolver->resolveForPost($post->ID);
        $pdfUrl = !empty($masterPdf['url']) ? $masterPdf['url'] : '';
        $pdfSource = !empty($masterPdf['source']) ? $masterPdf['source'] : '';
        $lastJob = get_post_meta($post->ID, 'chao_manuscript_worker_last_job', true);
        $lastResponse = get_post_meta($post->ID, 'chao_manuscript_worker_last_response', true);
        $lastError = get_post_meta($post->ID, 'chao_manuscript_worker_last_error', true);
        $lastRequest = get_post_meta($post->ID, 'chao_manuscript_worker_last_request', true);

        echo '<p>' . esc_html__('Use este painel para enviar ao pdf-worker o PDF mestre salvo no campo do manuscrito ou o primeiro bloco Arquivo do editor.', 'chao') . '</p>';

        if ($pdfUrl) {
            $processUrl = static::buildProcessUrl($post->ID, wp_create_nonce('chao_process_manuscript_pdf_' . $post->ID));
            $sourceLabel = __('Campo PDF mestre', 'chao');

            if ($pdfSource === 'block') {
                $sourceLabel = __('Bloco Arquivo', 'chao');
            } elseif ($pdfSource === 'content') {
                $sourceLabel = __('Conteudo salvo do editor', 'chao');
            } elseif ($pdfSource === 'attachment') {
                $sourceLabel = __('Arquivo anexado ao post', 'chao');
            }

            echo '<p><strong>' . esc_html__('PDF mestre:', 'chao') . '</strong> ';
            echo '<a href="' . esc_url($pdfUrl) . '" target="_blank" rel="noopener noreferrer">' . esc_html(basename($pdfUrl)) . '</a></p>';
            echo '<p><strong>' . esc_html__('Origem:', 'chao') . '</strong> ' . esc_html($sourceLabel) . '</p>';
            echo '<p><a class="button button-secondary" href="' . esc_url($processUrl) . '">' . esc_html__('Enviar para o worker local', 'chao') . '</a></p>';
        } else {
            echo '<p>' . esc_html__('Adicione um PDF mestre no campo do manuscrito ou insira um bloco Arquivo com um PDF para habilitar o teste do worker local.', 'chao') . '</p>';
        }

        if (!empty($lastError)) {
            echo '<p><strong>' . esc_html__('Ultimo erro:', 'chao') . '</strong><br>' . esc_html($lastError) . '</p>';
        }

        if (!empty($lastJob) && is_array($lastJob)) {
            echo '<p><strong>' . esc_html__('Ultimo job salvo:', 'chao') . '</strong></p>';
            echo '<p><strong>' . esc_html__('Job ID:', 'chao') . '</strong> ' . esc_html($lastJob['job_id'] ?? '') . '<br>';
            echo '<strong>' . esc_html__('Paginas:', 'chao') . '</strong> ' . esc_html((string) ($lastJob['page_count'] ?? 0)) . '<br>';
            echo '<strong>' . esc_html__('Revisao:', 'chao') . '</strong> <a href="#manuscript-worker-pages">' . esc_html__('ver tabela de paginas', 'chao') . '</a></p>';
        }

        if (!empty($lastResponse) && is_array($lastResponse)) {
            echo '<p><strong>' . esc_html__('Ultima resposta do worker:', 'chao') . '</strong></p>';
            echo '<pre style="max-height: 220px; overflow: auto; white-space: pre-wrap;">' . esc_html(wp_json_encode($lastResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
        }

        if (!empty($lastRequest) && is_array($lastRequest)) {
            echo '<p><strong>' . esc_html__('Ultimo payload enviado ao worker:', 'chao') . '</strong></p>';
            echo '<pre style="max-height: 180px; overflow: auto; white-space: pre-wrap;">' . esc_html(wp_json_encode($lastRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
        }
    }

    public static function renderPagesMetabox($post)
    {
        $lastJob = get_post_meta($post->ID, 'chao_manuscript_worker_last_job', true);
        $savedReview = get_post_meta($post->ID, 'chao_manuscript_page_review', true);
        $rows = static::buildWorkerPageRows(
            is_array($lastJob) ? $lastJob : [],
            is_array($savedReview) ? $savedReview : []
        );

        if (empty($rows)) {
            echo '<p>' . esc_html__('Envie o PDF ao worker local para gerar a lista de paginas extraidas.', 'chao') . '</p>';
            return;
        }

        wp_nonce_field('chao_save_manuscript_page_review_' . $post->ID, 'chao_manuscript_page_review_nonce');

        echo '<p>' . esc_html__('Resumo das paginas geradas pelo pdf-worker para este manuscrito.', 'chao') . '</p>';
        echo '<div style="overflow-x: auto;">';
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Pagina', 'chao') . '</th>';
        echo '<th>' . esc_html__('Arquivo', 'chao') . '</th>';
        echo '<th>' . esc_html__('HTR', 'chao') . '</th>';
        echo '<th>' . esc_html__('Texto indexado', 'chao') . '</th>';
        echo '<th>' . esc_html__('Status editorial', 'chao') . '</th>';
        echo '<th>' . esc_html__('Transcricao revisada', 'chao') . '</th>';
        echo '<th>' . esc_html__('Observacoes', 'chao') . '</th>';
        echo '<th>' . esc_html__('Job HTR', 'chao') . '</th>';
        echo '<th>' . esc_html__('Documento', 'chao') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $fieldBase = 'chao_manuscript_page_review[' . (int) $row['page_number'] . ']';
            echo '<tr>';
            echo '<td>' . esc_html((string) $row['page_number']) . '</td>';
            echo '<td><strong>' . esc_html($row['filename']) . '</strong><br><code>' . esc_html($row['image_path']) . '</code></td>';
            echo '<td>' . esc_html($row['htr_status']) . '</td>';
            echo '<td style="min-width: 240px;">' . esc_html(static::buildIndexedTextPreview($row)) . '</td>';
            echo '<td><select name="' . esc_attr($fieldBase . '[status]') . '">';

            foreach (static::getEditorialStatusOptions() as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"' . selected($row['editorial_status'], $value, false) . '>' . esc_html($label) . '</option>';
            }

            echo '</select></td>';
            echo '<td><textarea name="' . esc_attr($fieldBase . '[transcription]') . '" rows="4" style="width: 100%; min-width: 220px;">' . esc_textarea($row['review_transcription']) . '</textarea></td>';
            echo '<td><textarea name="' . esc_attr($fieldBase . '[notes]') . '" rows="3" style="width: 100%; min-width: 180px;">' . esc_textarea($row['review_notes']) . '</textarea></td>';
            echo '<td>' . esc_html($row['htr_job_id']) . '</td>';
            echo '<td>' . esc_html($row['htr_document_id']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
        echo '<p>' . esc_html__('Salve ou atualize o manuscrito para guardar a revisao por pagina.', 'chao') . '</p>';
    }

    public static function processPdf()
    {
        $requestData = static::extractProcessRequestData(
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
            !wp_verify_nonce($nonce, 'chao_process_manuscript_pdf_' . $postId)
        ) {
            wp_die(__('Solicitacao invalida.', 'chao'));
        }

        if (!current_user_can('edit_post', $postId)) {
            wp_die(__('Voce nao tem permissao para processar este manuscrito.', 'chao'));
        }

        $resolver = new MasterPdfResolver();
        $masterPdf = $resolver->resolveForPost($postId);
        $pdfUrl = !empty($masterPdf['url']) ? $masterPdf['url'] : '';

        if (!$pdfUrl) {
            update_post_meta($postId, 'chao_manuscript_worker_last_error', __('PDF mestre ausente ou invalido.', 'chao'));
            static::redirectBack($postId, 'missing_pdf');
        }

        $client = new WorkerClient();
        $requestPayload = $client->buildJobPayload($postId, $pdfUrl);
        update_post_meta($postId, 'chao_manuscript_worker_last_request', $requestPayload);
        $result = $client->submitPdfJob($postId, $pdfUrl);

        if (is_wp_error($result)) {
            update_post_meta($postId, 'chao_manuscript_worker_last_error', $result->get_error_message());
            update_post_meta($postId, 'chao_manuscript_worker_last_response', $result->get_error_data());
            static::redirectBack($postId, 'worker_error');
        }

        update_post_meta($postId, 'chao_manuscript_worker_last_error', '');
        update_post_meta($postId, 'chao_manuscript_worker_source', $masterPdf);
        update_post_meta($postId, 'chao_manuscript_worker_last_job', static::buildWorkerJobSnapshot($result));
        update_post_meta($postId, 'chao_manuscript_worker_last_response', $result);
        static::redirectBack($postId, 'success');
    }

    public static function importZip()
    {
        if (function_exists('wp_die')) {
            wp_die(__('Importacao de manuscrito via ZIP ainda nao implementada.', 'chao'));
        }

        exit(1);
    }

    public static function extractProcessRequestData(array $request)
    {
        return [
            'post_id' => isset($request['post_id']) ? (int) $request['post_id'] : 0,
            'nonce' => isset($request['chao_manuscript_worker_nonce']) ? (string) $request['chao_manuscript_worker_nonce'] : '',
        ];
    }

    public static function savePageReview($postId)
    {
        if (
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
            wp_is_post_revision($postId) ||
            !current_user_can('edit_post', $postId)
        ) {
            return;
        }

        if (
            !isset($_POST['chao_manuscript_page_review_nonce']) ||
            !wp_verify_nonce($_POST['chao_manuscript_page_review_nonce'], 'chao_save_manuscript_page_review_' . $postId)
        ) {
            return;
        }

        $reviewInput = isset($_POST['chao_manuscript_page_review']) && is_array($_POST['chao_manuscript_page_review'])
            ? $_POST['chao_manuscript_page_review']
            : [];

        update_post_meta($postId, 'chao_manuscript_page_review', static::normalizeWorkerPageReviewInput($reviewInput));
    }

    public static function renderAdminNotice()
    {
        $postId = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';
        $status = '';

        if (isset($_GET['chao_manuscript_import_notice'])) {
            $status = (string) $_GET['chao_manuscript_import_notice'];
        } elseif (isset($_GET['chao_manuscript_worker_notice'])) {
            $status = (string) $_GET['chao_manuscript_worker_notice'];
        }

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
                'message' => static::translate('PDF enviado ao worker local com sucesso.'),
            ],
            'missing_pdf' => [
                'type' => 'error',
                'message' => static::translate('Nao foi possivel enviar: PDF mestre ausente ou invalido.'),
            ],
            'worker_error' => [
                'type' => 'error',
                'message' => static::translate('O worker local retornou um erro. Veja os detalhes no metabox do manuscrito.'),
            ],
        ];

        return isset($notices[$status]) ? $notices[$status] : null;
    }

    public static function buildWorkerJobSnapshot(array $response)
    {
        $pages = [];

        foreach ((array) ($response['pages'] ?? []) as $page) {
            $htr = isset($page['htr']) && is_array($page['htr']) ? $page['htr'] : [];
            $pages[] = [
                'page_number' => isset($page['page_number']) ? (int) $page['page_number'] : 0,
                'filename' => isset($page['filename']) ? (string) $page['filename'] : '',
                'image_path' => isset($page['image_path']) ? (string) $page['image_path'] : '',
                'image_url' => isset($page['image_url']) ? (string) $page['image_url'] : '',
                'raw_text' => isset($page['raw_text']) ? (string) $page['raw_text'] : (isset($page['text']) ? (string) $page['text'] : ''),
                'layout_data' => isset($page['layout_data']) && is_array($page['layout_data']) ? $page['layout_data'] : [],
                'htr_status' => isset($htr['status']) ? (string) $htr['status'] : '',
                'htr_job_id' => isset($htr['job_id']) ? (string) $htr['job_id'] : '',
                'htr_page_id' => isset($htr['page_id']) ? (string) $htr['page_id'] : '',
                'htr_document_id' => isset($htr['document_id']) ? (string) $htr['document_id'] : '',
            ];
        }

        return [
            'job_id' => isset($response['job_id']) ? (string) $response['job_id'] : '',
            'manuscript_id' => isset($response['manuscript_id']) ? (int) $response['manuscript_id'] : 0,
            'page_count' => isset($response['page_count']) ? (int) $response['page_count'] : count($pages),
            'pages' => $pages,
            'stored_at' => static::currentTimestamp(),
        ];
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

    public static function buildWorkerPageRows(array $job, array $reviewData = [])
    {
        $rows = [];

        foreach ((array) ($job['pages'] ?? []) as $page) {
            $pageNumber = isset($page['page_number']) ? (int) $page['page_number'] : 0;
            $review = isset($reviewData[$pageNumber]) && is_array($reviewData[$pageNumber]) ? $reviewData[$pageNumber] : [];
            $rows[] = [
                'page_number' => $pageNumber,
                'filename' => isset($page['filename']) ? (string) $page['filename'] : '',
                'image_path' => isset($page['image_path']) ? (string) $page['image_path'] : '',
                'image_url' => isset($page['image_url']) ? (string) $page['image_url'] : '',
                'raw_text' => isset($page['raw_text']) ? (string) $page['raw_text'] : '',
                'htr_status' => !empty($page['htr_status']) ? (string) $page['htr_status'] : '-',
                'htr_job_id' => !empty($page['htr_job_id']) ? (string) $page['htr_job_id'] : '-',
                'htr_page_id' => !empty($page['htr_page_id']) ? (string) $page['htr_page_id'] : '-',
                'htr_document_id' => !empty($page['htr_document_id']) ? (string) $page['htr_document_id'] : '-',
                'editorial_status' => !empty($review['status']) ? (string) $review['status'] : 'pendente',
                'review_transcription' => !empty($review['transcription']) ? (string) $review['transcription'] : '',
                'review_notes' => !empty($review['notes']) ? (string) $review['notes'] : '',
            ];
        }

        return $rows;
    }

    public static function buildImportedPageRows(array $snapshot, array $reviewData = [])
    {
        return array_map(function ($page) use ($reviewData) {
            $pageNumber = isset($page['page_number']) ? (int) $page['page_number'] : 0;
            $review = isset($reviewData[$pageNumber]) && is_array($reviewData[$pageNumber]) ? $reviewData[$pageNumber] : [];

            return [
                'page_number' => $pageNumber,
                'basename' => isset($page['basename']) ? (string) $page['basename'] : '',
                'filename' => isset($page['filename']) ? (string) $page['filename'] : '',
                'image_url' => isset($page['image_url']) ? (string) $page['image_url'] : '',
                'raw_text' => isset($page['raw_text']) ? (string) $page['raw_text'] : '',
                'import_status' => isset($page['import_status']) ? (string) $page['import_status'] : 'pending',
                'highlight_status' => !empty($page['highlight_exact']) ? 'exato' : 'aproximado',
                'editorial_status' => !empty($review['status']) ? (string) $review['status'] : 'pendente',
            ];
        }, (array) ($snapshot['pages'] ?? []));
    }

    protected static function buildIndexedTextPreview(array $row)
    {
        $reviewedText = !empty($row['review_transcription']) ? trim((string) $row['review_transcription']) : '';
        $rawText = !empty($row['raw_text']) ? trim((string) $row['raw_text']) : '';
        $effectiveText = $reviewedText !== '' ? $reviewedText : $rawText;

        if ($effectiveText === '') {
            return static::translate('Sem texto indexado');
        }

        if (function_exists('mb_substr') && function_exists('mb_strlen')) {
            return mb_strlen($effectiveText, 'UTF-8') > 140
                ? mb_substr($effectiveText, 0, 140, 'UTF-8') . '...'
                : $effectiveText;
        }

        return strlen($effectiveText) > 140 ? substr($effectiveText, 0, 140) . '...' : $effectiveText;
    }

    public static function normalizeWorkerPageReviewInput(array $input)
    {
        $normalized = [];
        $validStatuses = array_keys(static::getEditorialStatusOptions());

        foreach ($input as $pageNumber => $pageReview) {
            $pageNumber = (int) $pageNumber;

            if ($pageNumber <= 0 || !is_array($pageReview)) {
                continue;
            }

            $status = isset($pageReview['status']) ? static::sanitizeKey($pageReview['status']) : 'pendente';

            if (!in_array($status, $validStatuses, true)) {
                $status = 'pendente';
            }

            $normalized[$pageNumber] = [
                'status' => $status,
                'transcription' => isset($pageReview['transcription']) ? trim(static::wpUnslash((string) $pageReview['transcription'])) : '',
                'notes' => isset($pageReview['notes']) ? trim(static::wpUnslash((string) $pageReview['notes'])) : '',
            ];
        }

        ksort($normalized);

        return $normalized;
    }

    public static function getEditorialStatusOptions()
    {
        return [
            'pendente' => static::translate('Pendente'),
            'em_revisao' => static::translate('Em revisao'),
            'revisado' => static::translate('Revisado'),
        ];
    }

    protected static function redirectBack($postId, $notice = null)
    {
        $queryArgs = [];

        if ($notice) {
            $queryArgs['chao_manuscript_worker_notice'] = $notice;
        }

        wp_safe_redirect(static::buildEditUrl($postId, $queryArgs));
        exit;
    }

    public static function buildProcessUrl($postId, $nonce, $adminBaseUrl = null)
    {
        $adminBaseUrl = $adminBaseUrl ?: admin_url();
        $basePath = rtrim($adminBaseUrl, '/') . '/admin-post.php';
        $queryArgs = [
            'action' => 'chao_process_manuscript_pdf',
            'post_id' => (int) $postId,
            'chao_manuscript_worker_nonce' => $nonce,
        ];

        if (function_exists('add_query_arg')) {
            return add_query_arg($queryArgs, $basePath);
        }

        return $basePath . '?' . http_build_query($queryArgs);
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
