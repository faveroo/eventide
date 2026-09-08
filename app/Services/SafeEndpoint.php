<?php

namespace App\Services;

use GuzzleHttp\Handler\CurlHandler;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class SafeEndpoint
{
    /** Validate all answers, then pin one address while preserving TLS hostname verification. */
    public function get(string $url, ?int $timeout = null): Response
    {
        $target = $this->validate($url);
        $timeout = max(1, min(30, $timeout ?? config('eventide.health_timeout_seconds')));
        if (! extension_loaded('curl')) {
            throw new InvalidArgumentException('Health checks require the cURL extension.');
        }
        $address = str_contains($target['address'], ':') ? '['.$target['address'].']' : $target['address'];

        return Http::withOptions([
            'allow_redirects' => false,
            'proxy' => '',
            'verify' => true,
            'protocols' => ['http', 'https'],
            'progress' => function ($downloadTotal, $downloaded) {
                if ($downloadTotal > 65536 || $downloaded > 65536) {
                    throw new \RuntimeException('Health response exceeds 64 KiB.');
                }
            },
            'curl' => [
                CURLOPT_RESOLVE => [$target['host'].':'.$target['port'].':'.$address],
            ],
        ])->setHandler(new CurlHandler)->connectTimeout(min(5, $timeout))
            ->timeout($timeout)
            // Bound response size even when an endpoint returns an unexpected body.
            ->withOptions(['stream' => true])->get($url);
    }

    /** @return array{host: string, port: int, address: string} */
    public function validate(string $url): array
    {
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new InvalidArgumentException('Invalid health endpoint.');
        }

        $parts = parse_url($url);

        if (
            ! $parts ||
            ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) ||
            empty($parts['host']) ||
            isset($parts['user']) ||
            isset($parts['pass']) ||
            isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException(
                'Use an HTTP or HTTPS endpoint without credentials or fragments.'
            );
        }

        $host = strtolower(trim($parts['host'], '[]'));
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);

        if (! in_array($port, [80, 443, 8000], true)) {
            throw new InvalidArgumentException(
                'Health endpoints must use port 80, 443 or 8000.'
            );
        }

        // Permite apenas localhost na porta 8000
        if ($host === 'localhost' && $port === 8000) {
            return [
                'host' => $host,
                'port' => $port,
                'address' => '127.0.0.1',
            ];
        }

        if (
            ! filter_var($host, FILTER_VALIDATE_IP) &&
            ! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
        ) {
            throw new InvalidArgumentException('Invalid endpoint hostname.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : $this->resolve($host);

        if ($addresses === []) {
            throw new InvalidArgumentException('Endpoint hostname has no addresses.');
        }

        foreach ($addresses as $address) {
            if (! $this->isPublicAddress($address)) {
                throw new InvalidArgumentException(
                    'Health endpoints must resolve only to public addresses.'
                );
            }
        }

        return [
            'host' => $host,
            'port' => $port,
            'address' => $addresses[0],
        ];
    }

    /** @return list<string> */
    protected function resolve(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        return array_values(array_unique(array_filter(array_map(
            fn ($record) => $record['ip'] ?? $record['ipv6'] ?? null, $records ?: [],
        ))));
    }

    public function isPublicAddress(string $address): bool
    {
        if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        $packed = inet_pton($address);
        if ($packed === false) {
            return false;
        }
        if (strlen($packed) === 16) {
            // Only native global unicast; exclude transition, protocol and documentation ranges.
            return (ord($packed[0]) & 0xE0) === 0x20
                && ! $this->inRange($address, '2001::', 23)
                && ! $this->inRange($address, '2001:db8::', 32)
                && ! $this->inRange($address, '2002::', 16)
                && ! $this->inRange($address, '3fff::', 20);
        }
        foreach ([['0.0.0.0', 8], ['100.64.0.0', 10], ['192.0.0.0', 24], ['192.0.2.0', 24],
            ['192.88.99.0', 24], ['198.18.0.0', 15], ['198.51.100.0', 24], ['203.0.113.0', 24], ['224.0.0.0', 3]] as [$network, $bits]) {
            if ($this->inRange($address, $network, $bits)) {
                return false;
            }
        }

        return true;
    }

    private function inRange(string $address, string $network, int $bits): bool
    {
        $address = inet_pton($address);
        $network = inet_pton($network);
        if ($address === false || $network === false || strlen($address) !== strlen($network)) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        $remaining = $bits % 8;

        return substr($address, 0, $bytes) === substr($network, 0, $bytes)
            && ($remaining === 0 || (ord($address[$bytes]) & (0xFF << (8 - $remaining))) === (ord($network[$bytes]) & (0xFF << (8 - $remaining))));
    }
}
