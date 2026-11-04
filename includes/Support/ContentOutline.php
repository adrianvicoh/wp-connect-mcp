<?php

namespace WpConnectMcp\Support;

defined('ABSPATH') || exit;

/**
 * Esquema de un contenido HTML (encabezados, enlaces y palabras), para analizarlo sin pasar
 * el texto entero por el contexto del agente.
 */
final class ContentOutline
{
    private const MAX_ITEMS = 200;

    /**
     * @return array{word_count: int, headings: list<array{level: int, text: string}>, links: list<array{href: string, text: string, internal: bool}>}
     */
    public static function of(string $html): array
    {
        return [
            'word_count' => self::wordCount($html),
            'headings' => self::headings($html),
            'links' => self::links($html),
        ];
    }

    /**
     * @return list<array{level: int, text: string}>
     */
    public static function headings(string $html): array
    {
        preg_match_all('/<h([1-6])(?:\s[^>]*)?>(.*?)<\/h\1\s*>/is', $html, $matches, PREG_SET_ORDER);

        $headings = [];
        foreach (array_slice($matches, 0, self::MAX_ITEMS) as $match) {
            $headings[] = ['level' => (int) $match[1], 'text' => self::text($match[2])];
        }

        return $headings;
    }

    /**
     * @return list<array{href: string, text: string, internal: bool}>
     */
    public static function links(string $html): array
    {
        preg_match_all('/<a\s[^>]*?href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a\s*>/is', $html, $matches, PREG_SET_ORDER);

        $siteHost = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
        $links = [];
        foreach (array_slice($matches, 0, self::MAX_ITEMS) as $match) {
            $href = html_entity_decode(trim($match[2]), ENT_QUOTES, 'UTF-8');
            $host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
            $links[] = [
                'href' => $href,
                'text' => self::text($match[3]),
                // Relativas (/ruta, #ancla) o del mismo dominio, con o sin www.
                'internal' => '' === $host
                    ? 0 !== stripos($href, 'mailto:') && 0 !== stripos($href, 'tel:')
                    : self::bareHost($host) === self::bareHost($siteHost),
            ];
        }

        return $links;
    }

    public static function wordCount(string $html): int
    {
        $text = self::text($html);

        return '' === $text ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }

    private static function text(string $html): string
    {
        $text = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private static function bareHost(string $host): string
    {
        return 0 === strpos($host, 'www.') ? substr($host, 4) : $host;
    }
}
