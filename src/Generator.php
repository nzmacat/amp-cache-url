<?php

namespace Nzm\AmpCacheUrl;

enum ServingMode: string
{
    case Content = 'content';
    case Viewer = 'viewer';
    case WebPackage = 'web_package';
    case Certificate = 'certificate';
    case Image = 'image';
}

class Generator
{
    public function generate(string $domainSuffix, string $url, ?ServingMode $servingMode = null): string
    {
        $url = trim($url);
        if (empty($url)) {
            throw new \InvalidArgumentException('URL cannot be empty.');
        }

        $canonicalUrl = parse_url($url);
        $queryString = $canonicalUrl['query'] ?? '';

        if (!isset($canonicalUrl['path'])) {
            $canonicalUrl['path'] = '';
        }

        $pathSegment = $this->getPathSegment($canonicalUrl['path'], $servingMode);
        $pathSegment .= $canonicalUrl['scheme'] === 'https' ? '/s/' : '/';

        $canonicalUrl['path'] = rtrim($canonicalUrl['path'], '/');

        $curlSub = $this->createCurlsSubdomain($canonicalUrl['host']);

        $cacheUrl = 'https';
        $cacheUrl .= '://' . $curlSub . '.' . $domainSuffix;
        $cacheUrl .= $pathSegment;
        $cacheUrl .= $canonicalUrl['host'];
        $cacheUrl .= $canonicalUrl['path'];

        if (!empty($queryString)) {
            $cacheUrl .= '?' . $queryString;
        }

        return $cacheUrl;
    }

    private function getCurlsEncoding(string $domain): string
    {
        $domain = idn_to_utf8($domain);
        $domain = str_replace('-', '--', $domain);
        $domain = str_replace('.', '-', $domain);

        $ascii = idn_to_ascii($domain);

        $domain = $ascii ? $ascii : $domain;

        $domain = strtolower($domain);

        return $domain;
    }

    private function encodeHexToBase32(string $hexString): string
    {
        $initialPadding = 'ffffffffff';
        $finalPadding = '000000';
        $paddedHexString = $initialPadding . $hexString . $finalPadding;
        $encodedString = $this->encode32($paddedHexString);

        $bitsPerHexChar = 4;
        $bitsPerBase32Char = 5;
        $numInitialPaddingChars = (strlen($initialPadding) * $bitsPerHexChar) / $bitsPerBase32Char;
        $numHexStringChars = ceil((strlen($hexString) * $bitsPerHexChar) / $bitsPerBase32Char);

        $result = substr($encodedString, $numInitialPaddingChars, $numHexStringChars);
        return $result;
    }

    private function encode32(string $paddedHexString): string
    {
        $bytes = [];
        preg_match_all('/.{1,2}/', $paddedHexString, $matches);
        foreach ($matches[0] as $pair) {
            $bytes[] = hexdec($pair);
        }

        // Split into groups of 5 and convert to base32.
        $base32 = 'abcdefghijklmnopqrstuvwxyz234567';
        $leftover = count($bytes) % 5;
        $quanta = floor(count($bytes) / 5);
        $parts = [];

        for ($i = 0; $i < 5 - $leftover; $i++) {
            $bytes[] = hexdec('00');
        }

        $quanta += ($leftover > 0) ? 1 : 0;

        for ($i = 0; $i < $quanta; $i++) {
            $parts[] = $base32[$bytes[$i * 5] >> 3];
            $parts[] = $base32[(($bytes[$i * 5] & 0x07) << 2) | ($bytes[$i * 5 + 1] >> 6)];
            $parts[] = $base32[($bytes[$i * 5 + 1] & 0x3f) >> 1];
            $parts[] = $base32[((($bytes[$i * 5 + 1] & 0x01) << 4) | ($bytes[$i * 5 + 2] >> 4))];
            $parts[] = $base32[((($bytes[$i * 5 + 2] & 0x0f) << 1) | ($bytes[$i * 5 + 3] >> 7))];
            $parts[] = $base32[($bytes[$i * 5 + 3] & 0x7f) >> 2];
            $parts[] = $base32[((($bytes[$i * 5 + 3] & 0x03) << 3) | ($bytes[$i * 5 + 4] >> 5))];
            $parts[] = $base32[$bytes[$i * 5 + 4] & 0x1f];
        }

        $replace = ($leftover == 1) ? 6 : (($leftover == 2) ? 4 : (($leftover == 3) ? 3 : (($leftover == 4) ? 1 : 0)));

        for ($i = 0; $i < $replace; $i++, array_pop($parts)) {
        }

        for ($i = 0; $i < $replace; $i++, array_push($parts, '=')) {
        }

        return implode('', $parts);
    }

    private function fallbackToHash(string $url): string
    {
        $hash = hash('sha256', $url);
        return $this->encodeHexToBase32($hash);
    }

    public function createCurlsSubdomain(string $domain): string
    {
        if ($this->isEligibleForHumanReadableCacheEncoding($domain)) {
            $curlsEncoding = $this->getCurlsEncoding($domain);

            if (!$curlsEncoding || strlen($curlsEncoding) > 63) {
                return $this->fallbackToHash($domain);
            }

            if ($this->hasInvalidHyphens($curlsEncoding)) {
                return '0-' . $curlsEncoding . '-0';
            }

            return $curlsEncoding;
        }

        return $this->fallbackToHash($domain);
    }

    private function hasInvalidHyphens(string $domain): bool
    {
        return substr($domain, 2, 2) == '--' && substr($domain, 0, 2) != 'xn';
    }

    private function hasRtlAndLtrChars(string $unicode): bool
    {
        $ltrChars = 'A-Za-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u02B8\u0300-\u0590\u0800-\u1FFF\u200E\u2C00-\uFB1C\uFE00-\uFE6F\uFEFD-\uFFFF';
        $rtlChars = '\u0591-\u06EF\u06FA-\u07FF\u200F\uFB1D-\uFDFF\uFE70-\uFEFC';

        $hasLtrChars = mb_ereg('[' . $ltrChars . ']', $unicode);
        $hasRtlChars = mb_ereg('[' . $rtlChars . ']', $unicode);

        return !($hasLtrChars && $hasRtlChars);
    }

    private function isEligibleForHumanReadableCacheEncoding(string $domain): bool
    {
        if ($this->hasInvalidHyphens($domain)) {
            return false;
        }

        $unicode = idn_to_utf8($domain);

        $hasPassed = $this->hasRtlAndLtrChars($unicode);

        return strlen($domain) <= 63 && $hasPassed && strpos($domain, '.') !== false;
    }

    public function getPathSegment(string $path, ?ServingMode $servingMode): string
    {
        if ($this->isImagePath($path)) {
            return '/i';
        }

        if ($this->isFontPath($path)) {
            return '/r';
        }

        if ($servingMode === ServingMode::Viewer) {
            return '/v';
        }

        return '/c';
    }

    public function isImagePath(string $path): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'svg', 'svgz', 'tif', 'tiff'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($extension, $imageExtensions);
    }

    public function isFontPath(string $path): bool
    {
        $fontExtensions = ['ttf', 'otf', 'woff', 'woff2'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($extension, $fontExtensions);
    }
}