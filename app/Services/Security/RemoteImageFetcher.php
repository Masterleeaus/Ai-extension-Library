<?php

declare(strict_types=1);

namespace App\Services\Security;

use finfo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RemoteImageFetcher
{
    public const MAX_BYTES = 20_971_520;

    private const MIME_EXTENSIONS = [
        'image/avif' => 'avif',
        'image/bmp'  => 'bmp',
        'image/gif'  => 'gif',
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private const BLOCKED_CIDRS = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '::/128',
        '::1/128',
        '100::/64',
        '2001:10::/28',
        '2001:db8::/32',
        'fc00::/7',
        'fe80::/10',
        'ff00::/8',
    ];

    private const BLOCKED_HOST_SUFFIXES = [
        'localhost',
        '.localhost',
        '.local',
        '.internal',
        '.home',
        '.lan',
        '.test',
        '.invalid',
    ];

    /**
     * @return array{host: string, ip: string, ip_literal: bool}
     */
    public function resolveTarget(string $url): array
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https') {
            throw new RuntimeException('Remote images must use HTTPS.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('Remote image URLs cannot contain credentials.');
        }

        if (($parts['port'] ?? 443) !== 443) {
            throw new RuntimeException('Remote image URLs must use port 443.');
        }

        $rawHost = (string) ($parts['host'] ?? '');
        if ($rawHost === '' || str_ends_with($rawHost, '.')) {
            throw new RuntimeException('Remote image URL host is invalid.');
        }

        $host = strtolower(trim($rawHost, '[]'));
        if ($host === '' || strlen($host) > 253 || str_contains($host, '%')) {
            throw new RuntimeException('Remote image URL host is invalid.');
        }

        foreach (self::BLOCKED_HOST_SUFFIXES as $suffix) {
            if ($host === $suffix || str_ends_with($host, $suffix)) {
                throw new RuntimeException('Local hostnames are not allowed.');
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            if (! $this->isPublicIp($host)) {
                throw new RuntimeException('Private or reserved remote image targets are not allowed.');
            }

            return ['host' => $host, 'ip' => $host, 'ip_literal' => true];
        }

        if (! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $host)) {
            throw new RuntimeException('Remote image URL host is invalid.');
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        $ips = [];

        if (is_array($records)) {
            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;
                if (is_string($ip) && $ip !== '') {
                    $ips[] = $ip;
                }
            }
        }

        if ($ips === []) {
            foreach (gethostbynamel($host) ?: [] as $ip) {
                $ips[] = $ip;
            }
        }

        $ips = array_values(array_unique($ips));
        if ($ips === []) {
            throw new RuntimeException('Remote image host could not be resolved.');
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new RuntimeException('Remote image host resolves to a private or reserved address.');
            }
        }

        return ['host' => $host, 'ip' => $ips[0], 'ip_literal' => false];
    }

    /**
     * @return array{path: string, mime: string, extension: string, size: int}
     */
    public function fetch(string $url): array
    {
        $target = $this->resolveTarget($url);
        $maxBytes = max(1, (int) config('titan_create.security.remote_image_max_bytes', self::MAX_BYTES));
        $tempPath = tempnam(sys_get_temp_dir(), 'titan-remote-image-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create a temporary image file.');
        }

        try {
            $curlOptions = [];

            if (! $target['ip_literal']) {
                if (! defined('CURLOPT_RESOLVE')) {
                    throw new RuntimeException('DNS pinning is unavailable.');
                }

                $pinnedIp = str_contains($target['ip'], ':') ? '[' . $target['ip'] . ']' : $target['ip'];
                $curlOptions[CURLOPT_RESOLVE] = [sprintf('%s:443:%s', $target['host'], $pinnedIp)];
            }

            if (defined('CURLOPT_PROXY')) {
                $curlOptions[CURLOPT_PROXY] = '';
            }

            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->withHeaders([
                    'Accept' => implode(', ', array_keys(self::MIME_EXTENSIONS)),
                ])
                ->withOptions([
                    'allow_redirects' => false,
                    'http_errors'     => false,
                    'sink'            => $tempPath,
                    'progress'        => static function (
                        float $downloadTotal,
                        float $downloaded,
                        float $uploadTotal,
                        float $uploaded
                    ) use ($maxBytes): void {
                        if ($downloadTotal > $maxBytes || $downloaded > $maxBytes) {
                            throw new RuntimeException('Remote image exceeds the allowed size.');
                        }
                    },
                    'curl' => $curlOptions,
                ])
                ->get($url);

            $status = $response->status();
            if ($status >= 300 && $status < 400) {
                throw new RuntimeException('Remote image redirects are not allowed.');
            }

            if (! $response->successful()) {
                throw new RuntimeException('Remote image request was unsuccessful.');
            }

            $size = is_file($tempPath) ? filesize($tempPath) : false;
            if ($size === false || $size < 1 || $size > $maxBytes) {
                throw new RuntimeException('Remote image size is invalid.');
            }

            $declaredMime = strtolower(trim(explode(';', (string) $response->header('Content-Type'), 2)[0]));
            if ($declaredMime !== ''
                && $declaredMime !== 'application/octet-stream'
                && ! array_key_exists($declaredMime, self::MIME_EXTENSIONS)) {
                throw new RuntimeException('Remote response is not a supported image type.');
            }

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tempPath);
            if (! is_string($mime) || ! isset(self::MIME_EXTENSIONS[$mime])) {
                throw new RuntimeException('Downloaded content is not a supported raster image.');
            }

            return [
                'path'      => $tempPath,
                'mime'      => $mime,
                'extension' => self::MIME_EXTENSIONS[$mime],
                'size'      => (int) $size,
            ];
        } catch (Throwable $exception) {
            @unlink($tempPath);

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Remote image download failed.', 0, $exception);
        }
    }

    public function store(string $url, string $disk, string $directory): string
    {
        $download = $this->fetch($url);
        $relativePath = trim($directory, '/') . '/' . Str::uuid() . '.' . $download['extension'];
        $stream = fopen($download['path'], 'rb');

        try {
            if ($stream === false || ! Storage::disk($disk)->put($relativePath, $stream)) {
                throw new RuntimeException('Unable to store the downloaded image.');
            }

            return $relativePath;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            @unlink($download['path']);
        }
    }

    private function isPublicIp(string $ip): bool
    {
        if (filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false) {
            return false;
        }

        foreach (self::BLOCKED_CIDRS as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$network, $prefixLength] = explode('/', $cidr, 2);
        $ipBytes = inet_pton($ip);
        $networkBytes = inet_pton($network);

        if ($ipBytes === false || $networkBytes === false || strlen($ipBytes) !== strlen($networkBytes)) {
            return false;
        }

        $prefix = (int) $prefixLength;
        $maxBits = strlen($ipBytes) * 8;
        if ($prefix < 0 || $prefix > $maxBits) {
            return false;
        }

        $wholeBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($wholeBytes > 0 && substr($ipBytes, 0, $wholeBytes) !== substr($networkBytes, 0, $wholeBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ipBytes[$wholeBytes]) & $mask) === (ord($networkBytes[$wholeBytes]) & $mask);
    }
}
