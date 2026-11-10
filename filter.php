<?php
declare(strict_types=1);

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string {
        return strtolower($string);
    }
}

final class ContentFilter
{
    /** @var array<string, string[]> */
    private static array $wordlists = [];

    private const BADWORDS_FILE = __DIR__ . '/badwords.txt';

    private const URL_TLDS = 'com|net|org|fr|xyz|ru|me|top|io|tv|biz|info|site|online|gg|co|uk|de|eu|ca|nl|es|it|pl|app|dev|link|live|cc|pw|su';

    /**
     * Normalisation légère pour la détection d'URL.
     */
    private static function normalizeForUrls(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, ['@' => 'a', '$' => 's']);
        $text = preg_replace('/\s*(?:\(|\[)?(?:dot|point|\.)(?:\)|\])?\s*/iu', '.', $text) ?? $text;
        $text = preg_replace('/\s*(?:\(|\[)?(?:slash|\/)(?:\)|\])?\s*/iu', '/', $text) ?? $text;
        $text = str_replace(['(at)', ' arobase '], ['@', '@'], $text);
        $text = preg_replace('/([a-z0-9_-]{2,})\s+(?:' . self::URL_TLDS . ')\b/iu', '$1.$0', $text) ?? $text;
        $text = preg_replace('/([a-z0-9_-]+)\.\s+/iu', '$1.', $text) ?? $text;

        return $text;
    }

    /**
     * Normalisation agressive pour la détection des termes interdits.
     */
    private static function normalizeForSlurs(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        $leet = [
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a',
            '@' => 'a', '$' => 's', '5' => 's', '7' => 't', '!' => 'i',
            '<' => 'c', '(' => 'c', '+' => 't',
        ];
        $text = strtr($text, $leet);
        $text = preg_replace('/[^\p{L}]+/u', '', $text) ?? $text;
        $text = preg_replace('/(.)\1{2,}/u', '$1$1', $text) ?? $text;

        return $text;
    }

    /**
     * Détecte les URLs, domaines et IPs brutes.
     */
    public static function hasLinks(string $text): bool
    {
        $n = self::normalizeForUrls($text);

        $patterns = [
            '/(?:https?:\/\/|ftp:\/\/|www\.)[^\s]+/iu',
            '/[a-z0-9-]+(?:\.[a-z0-9-]+)*\.(?:' . self::URL_TLDS . ')\b/iu',
            '/\b\d{1,3}(?:\.\d{1,3}){3}\b/',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $n)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Charge le fichier de liste noire en cache mémoire statique.
     * @return string[]
     */
    public static function loadWordlist(string $path = self::BADWORDS_FILE): array
    {
        if (isset(self::$wordlists[$path])) {
            return self::$wordlists[$path];
        }

        if (!is_file($path)) {
            return self::$wordlists[$path] = [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return self::$wordlists[$path] = [];
        }

        $words = array_values(array_filter(
            array_map('trim', $lines),
            static fn (string $line): bool => $line !== '' && !str_starts_with($line, '#')
        ));

        return self::$wordlists[$path] = $words;
    }

    /**
     * Détecte les slurs via la liste noire.
     */
    public static function hasSlurs(string $text, string $wordlistPath = self::BADWORDS_FILE): bool
    {
        $badwords = self::loadWordlist($wordlistPath);
        if ($badwords === []) {
            return false;
        }

        $n = self::normalizeForSlurs($text);

        $escaped = array_map(
            static fn (string $w): string => preg_quote(mb_strtolower(trim($w), 'UTF-8'), '/'),
            $badwords
        );

        $pattern = '/(?:' . implode('|', $escaped) . ')/iu';

        return (bool) preg_match($pattern, $n);
    }

    /**
     * Valide le message : renvoie null si OK, sinon le message de rejet.
     */
    public static function check(string $text): ?string
    {
        if (self::hasLinks($text) || self::hasSlurs($text)) {
            return 'Forbidden message or nickname content.';
        }

        return null;
    }
}