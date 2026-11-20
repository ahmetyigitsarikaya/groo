<?php

declare(strict_types=1);

namespace Plugins\Content\Support;

use Plugins\Content\Services\PostService;

final class ContentHelper
{
    public static function getPosts(int $limit = 5): array
    {
        return (new PostService())->getPublishedPosts($limit, 1);
    }

    public static function getPost(string $slug): ?array
    {
        return (new PostService())->getBySlug($slug);
    }

    public static function renderBlocks(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            if (!is_array($block) || !isset($block['type'])) {
                continue;
            }

            $type = (string) $block['type'];

            if ($type === 'text' || $type === 'html' || $type === 'wysiwyg') {
                $content = (string) ($block['content'] ?? '');
                if (trim($content) === '') {
                    continue;
                }

                if ($content === strip_tags($content)) {
                    $content = '<p>' . nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . '</p>';
                }

                $html .= '<div class="content-block content-block-text">' . $content . '</div>' . "\n";
            } elseif ($type === 'image') {
                $url = (string) ($block['url'] ?? $block['src'] ?? '');
                if (trim($url) === '') {
                    continue;
                }

                $alt = htmlspecialchars((string) ($block['alt'] ?? ''), ENT_QUOTES, 'UTF-8');
                $caption = (string) ($block['caption'] ?? '');
                $align = (string) ($block['align'] ?? 'center');

                $alignClass = 'block-align-center';
                if ($align === 'left') {
                    $alignClass = 'block-align-left';
                } elseif ($align === 'right') {
                    $alignClass = 'block-align-right';
                }

                $html .= '<figure class="content-block content-block-image ' . $alignClass . '">';
                $html .= '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" />';
                if (trim($caption) !== '') {
                    $html .= '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>';
                }
                $html .= '</figure>' . "\n";
            }
        }

        return $html;
    }
}
