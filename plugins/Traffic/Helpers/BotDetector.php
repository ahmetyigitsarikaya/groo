<?php

declare(strict_types=1);

namespace Plugins\Traffic\Helpers;

/**
 * BotDetector - User-Agent üzerinden Bot ve Cihaz tespiti yapar.
 */
final class BotDetector
{
    /** En yaygın botların listesi (regex kısımları) */
    private const BOTS = [
        'Googlebot'       => 'Googlebot',
        'Bingbot'         => 'bingbot',
        'YandexBot'       => 'YandexBot',
        'Baiduspider'     => 'Baiduspider',
        'AhrefsBot'       => 'AhrefsBot',
        'SemrushBot'      => 'SemrushBot',
        'GPTBot'          => 'GPTBot',
        'Curl'            => 'curl',
        'PythonRequests'  => 'python-requests',
        'Wget'            => 'Wget',
        'Twitterbot'      => 'Twitterbot',
        'FacebookBot'     => 'facebookexternalhit',
        'Applebot'        => 'Applebot',
        'Slurp'           => 'Slurp',
        'DuckDuckBot'     => 'DuckDuckBot',
        'Discordbot'      => 'Discordbot',
        'TelegramBot'     => 'TelegramBot',
        'LinkedInBot'     => 'LinkedInBot',
        'WhatsApp'        => 'WhatsApp',
        'MJ12bot'         => 'MJ12bot',
        'Bytespider'      => 'Bytespider',
        'DotBot'          => 'DotBot',
        'SeznamBot'       => 'SeznamBot',
    ];

    /** Genel bot tespiti için fallback regex (içinde bot, spider, crawler vb. geçenler) */
    private const FALLBACK_REGEX = '/(bot|crawler|spider|slurp|archiver|scraper|scanner|monitor|ping)/i';

    private string $userAgent;
    private bool $isBot = false;
    private ?string $botName = null;
    private string $deviceType = 'desktop';

    public function __construct(?string $userAgent)
    {
        $this->userAgent = trim((string) $userAgent);
        $this->parse();
    }

    public function isBot(): bool
    {
        return $this->isBot;
    }

    public function getBotName(): ?string
    {
        return $this->botName;
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    private function parse(): void
    {
        if ($this->userAgent === '') {
            // User-agent yoksa bot say
            $this->isBot = true;
            $this->botName = 'Unknown (Empty UA)';
            return;
        }

        // 1. Bilinen Bot Tespiti
        foreach (self::BOTS as $name => $pattern) {
            if (stripos($this->userAgent, $pattern) !== false) {
                $this->isBot = true;
                $this->botName = $name;
                // Botlar genellikle masaüstü simüle eder (veya cihaz türü önemsizdir)
                $this->deviceType = 'desktop';
                return;
            }
        }

        // 2. Fallback Bot Tespiti
        if (preg_match(self::FALLBACK_REGEX, $this->userAgent)) {
            $this->isBot = true;
            $this->botName = 'Generic Bot';
            $this->deviceType = 'desktop';
            return;
        }

        // 3. Cihaz Tespiti (Sadece gerçek kullanıcılar için)
        // Basit Regex (Tablet, Mobile)
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $this->userAgent)) {
            $this->deviceType = 'tablet';
        } elseif (preg_match('/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/i', $this->userAgent)) {
            $this->deviceType = 'mobile';
        } else {
            $this->deviceType = 'desktop';
        }
    }
}
