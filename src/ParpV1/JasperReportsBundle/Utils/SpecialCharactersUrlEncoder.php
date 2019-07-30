<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Utils;

/**
 * SpecialCharactersUrlEncoder
 *
 * Koduje znaki specjalne w częściach URL'a.
 */
class SpecialCharactersUrlEncoder
{
    /**
     * @var string
     */
    const HTTP = 'http://';

    /**
     * @var string
     */
    const HTTPS = 'https://';

    /**
     * @var null|string
     */
    private static $protocol;

    /**
     * @var string
     */
    private static $url;

    /**
     * Koduje częsci url opcjonalnie pomijając znak '/'.
     *
     * @param string $url
     * @param bool $ignoreSlash
     *
     * @return string
     */
    public static function encode(string $url, bool $ignoreSlash = true): string
    {
        if (false !== strpos($url, '%')) {
            return $url;
        }

        self::$url = $url;
        self::matchProtocol(true);

        if (!$ignoreSlash) {
            return urlencode(self::$url);
        }

        $urlParts = explode('/', self::$url);
        foreach ($urlParts as $key => $value) {
            $urlParts[$key] = urlencode($value);
        }

        self::$url = implode('/', $urlParts);

        return self::$protocol? self::$protocol . self::$url : self::$url;
    }

    /**
     * Obcina protokół z URL.
     *
     * @return void
     */
    private static function cutProtocol(): void
    {
        self::$url = str_replace(self::$protocol, '', self::$url);
    }

    /**
     * Dopasowuje protokół z URL'a.
     * Opcjonalnie obcina go.
     *
     * @param bool $cutFromUrl
     *
     * @return void
     */
    private static function matchProtocol(bool $cutFromUrl = false): void
    {
        $protocol = null;

        if (false !== strpos(self::$url, self::HTTP)) {
            $protocol = self::HTTP;
        }
        if (false !== strpos(self::$url, self::HTTPS)) {
            $protocol = self::HTTPS;
        }

        self::$protocol = $protocol;

        if (null !== $protocol && $cutFromUrl) {
            self::cutProtocol();
        }
    }
}
