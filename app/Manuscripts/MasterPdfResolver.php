<?php

namespace App\Manuscripts;

class MasterPdfResolver
{
    public function resolveForPost($postId)
    {
        $fieldSource = $this->resolveFromField($postId);
        if ($fieldSource) {
            return $fieldSource;
        }

        $contentSource = $this->resolveFromBlocks($postId);
        if ($contentSource) {
            return $contentSource;
        }

        return $this->resolveFromAttachments($postId);
    }

    public function extractSourceFromBlocks(array $blocks)
    {
        foreach ($blocks as $block) {
            if (($block['blockName'] ?? '') === 'core/file') {
                $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];
                $id = !empty($attrs['id']) ? (int) $attrs['id'] : 0;
                $url = !empty($attrs['href']) ? (string) $attrs['href'] : '';

                if ($id || $url) {
                    return [
                        'id' => $id,
                        'url' => $url,
                        'source' => 'block',
                    ];
                }
            }

            if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
                $nested = $this->extractSourceFromBlocks($block['innerBlocks']);
                if ($nested) {
                    return $nested;
                }
            }
        }

        return null;
    }

    protected function resolveFromField($postId)
    {
        $value = 0;

        if ($this->hasFunction('get_field')) {
            $value = (int) $this->callFunction('get_field', ['manuscript_master_pdf', $postId, false]);
        }

        if (!$value) {
            $value = (int) $this->callFunction('get_post_meta', [$postId, 'manuscript_master_pdf', true]);
        }

        if (!$value) {
            return null;
        }

        $url = $this->callFunction('wp_get_attachment_url', [$value]);

        if (!$url) {
            return null;
        }

        return [
            'id' => $value,
            'url' => $url,
            'source' => 'field',
        ];
    }

    protected function resolveFromBlocks($postId)
    {
        $content = (string) $this->callFunction('get_post_field', ['post_content', $postId]);

        if ($content === '') {
            return null;
        }

        return $this->extractSourceFromContent($content);
    }

    public function extractSourceFromContent($content)
    {
        $content = (string) $content;

        if ($content === '') {
            return null;
        }

        $source = null;

        if ($this->hasFunction('parse_blocks')) {
            $source = $this->extractSourceFromBlocks($this->callFunction('parse_blocks', [$content]));
        }

        if (!$source) {
            $source = $this->extractSourceFromHtml($content);
        }

        if (!$source) {
            return null;
        }

        if (empty($source['url']) && !empty($source['id'])) {
            $source['url'] = $this->callFunction('wp_get_attachment_url', [(int) $source['id']]);
        }

        return !empty($source['url']) ? $source : null;
    }

    protected function extractSourceFromHtml($content)
    {
        if (!preg_match('~href=(["\'])([^"\']+\.pdf(?:\?[^"\']*)?)\1~i', (string) $content, $matches)) {
            return null;
        }

        return [
            'id' => 0,
            'url' => html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8'),
            'source' => 'content',
        ];
    }

    protected function resolveFromAttachments($postId)
    {
        if (!$this->hasFunction('get_attached_media')) {
            return null;
        }

        $attachments = $this->callFunction('get_attached_media', ['application/pdf', $postId]);

        if (empty($attachments) || !is_array($attachments)) {
            return null;
        }

        $attachment = reset($attachments);
        $attachmentId = !empty($attachment->ID) ? (int) $attachment->ID : 0;

        if (!$attachmentId) {
            return null;
        }

        $url = $this->callFunction('wp_get_attachment_url', [$attachmentId]);

        if (!$url) {
            return null;
        }

        return [
            'id' => $attachmentId,
            'url' => $url,
            'source' => 'attachment',
        ];
    }

    protected function hasFunction($functionName)
    {
        return function_exists(__NAMESPACE__ . '\\' . $functionName) || function_exists($functionName);
    }

    protected function callFunction($functionName, array $arguments)
    {
        $namespacedFunction = __NAMESPACE__ . '\\' . $functionName;

        if (function_exists($namespacedFunction)) {
            return call_user_func_array($namespacedFunction, $arguments);
        }

        return call_user_func_array($functionName, $arguments);
    }
}
