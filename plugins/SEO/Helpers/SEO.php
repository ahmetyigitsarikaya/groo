<?php

declare(strict_types=1);

namespace Plugins\SEO\Helpers;

use Plugins\SEO\Services\SEOEngine;

final class SEO
{
    public static function render(?string $url = null): void
    {
        if ($url === null) {
            $url = $_SERVER['REQUEST_URI'] ?? '/';
        }

        $engine = new SEOEngine();
        $meta = $engine->getSEODataForUrl($url);

        $html = [];
        $html[] = '<!-- SEO Engine by Groo -->';
        
        // Title
        if (!empty($meta['title'])) {
            $html[] = sprintf('<title>%s</title>', htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8'));
        }

        // Description
        if (!empty($meta['description'])) {
            $html[] = sprintf('<meta name="description" content="%s">', htmlspecialchars($meta['description'], ENT_QUOTES, 'UTF-8'));
        }

        // Keywords
        if (!empty($meta['keywords'])) {
            $html[] = sprintf('<meta name="keywords" content="%s">', htmlspecialchars($meta['keywords'], ENT_QUOTES, 'UTF-8'));
        }

        // Robots
        $robots = [];
        if (!empty($meta['noindex'])) {
            $robots[] = 'noindex';
        } else {
            $robots[] = 'index';
        }
        
        if (!empty($meta['nofollow'])) {
            $robots[] = 'nofollow';
        } else {
            $robots[] = 'follow';
        }
        $html[] = sprintf('<meta name="robots" content="%s">', implode(', ', $robots));

        // Canonical
        if (!empty($meta['canonical_url'])) {
            $html[] = sprintf('<link rel="canonical" href="%s">', htmlspecialchars($meta['canonical_url'], ENT_QUOTES, 'UTF-8'));
        }

        // Open Graph
        if (!empty($meta['og_title'])) {
            $html[] = sprintf('<meta property="og:title" content="%s">', htmlspecialchars($meta['og_title'], ENT_QUOTES, 'UTF-8'));
        }
        if (!empty($meta['og_description'])) {
            $html[] = sprintf('<meta property="og:description" content="%s">', htmlspecialchars($meta['og_description'], ENT_QUOTES, 'UTF-8'));
        }
        if (!empty($meta['og_image'])) {
            $html[] = sprintf('<meta property="og:image" content="%s">', htmlspecialchars($meta['og_image'], ENT_QUOTES, 'UTF-8'));
        }
        $html[] = sprintf('<meta property="og:url" content="%s">', htmlspecialchars($meta['canonical_url'] ?? '', ENT_QUOTES, 'UTF-8'));
        $html[] = '<meta property="og:type" content="website">';

        // Twitter Cards
        $html[] = '<meta name="twitter:card" content="summary_large_image">';
        if (!empty($meta['og_title'])) {
            $html[] = sprintf('<meta name="twitter:title" content="%s">', htmlspecialchars($meta['og_title'], ENT_QUOTES, 'UTF-8'));
        }
        if (!empty($meta['og_description'])) {
            $html[] = sprintf('<meta name="twitter:description" content="%s">', htmlspecialchars($meta['og_description'], ENT_QUOTES, 'UTF-8'));
        }
        if (!empty($meta['og_image'])) {
            $html[] = sprintf('<meta name="twitter:image" content="%s">', htmlspecialchars($meta['og_image'], ENT_QUOTES, 'UTF-8'));
        }

        // Verifications
        $googleVerification = $engine->getSetting('google_site_verification');
        if (!empty($googleVerification)) {
            $html[] = sprintf('<meta name="google-site-verification" content="%s">', htmlspecialchars($googleVerification, ENT_QUOTES, 'UTF-8'));
        }

        $bingVerification = $engine->getSetting('bing_site_verification');
        if (!empty($bingVerification)) {
            $html[] = sprintf('<meta name="msvalidate.01" content="%s">', htmlspecialchars($bingVerification, ENT_QUOTES, 'UTF-8'));
        }

        // JSON-LD Schema
        if (!empty($meta['schema_type'])) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => $meta['schema_type'],
                'url' => $meta['canonical_url'] ?? '',
                'name' => $meta['title'] ?? '',
                'description' => $meta['description'] ?? ''
            ];
            $html[] = '<script type="application/ld+json">';
            $html[] = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $html[] = '</script>';
        }

        $html[] = '<!-- /SEO Engine -->';

        echo implode("\n", $html) . "\n";
    }
}
