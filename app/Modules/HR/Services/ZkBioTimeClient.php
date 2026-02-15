<?php

namespace App\Modules\HR\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZkBioTimeClient
{
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('zkbiotime');
    }

    public function getTransactions(array $params): array
    {
        return $this->request('get', '/iclock/api/transactions/', $params);
    }

    protected function request(string $method, string $path, array $query = []): array
    {
        $url = rtrim($this->config['base_url'], '/') . $path;
        $response = $this->http()->{$method}($url, $query);

        if (! $response->successful()) {
            throw new RuntimeException('ZKBioTime request failed: ' . $response->status() . ' ' . $response->body());
        }

        return $response->json() ?? [];
    }

    protected function http(): PendingRequest
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $token = $this->getToken();
        if (! empty($token)) {
            $headers['Authorization'] = $this->formatAuthHeader($token);
        }

        return Http::timeout((int) ($this->config['timeout'] ?? 30))
            ->withHeaders($headers)
            ->withOptions(['verify' => (bool) ($this->config['verify_ssl'] ?? true)]);
    }

    protected function getToken(): ?string
    {
        $token = $this->config['token'] ?? null;
        if (! empty($token)) {
            return $token;
        }

        $cacheMinutes = (int) ($this->config['token_cache_minutes'] ?? 0);
        if ($cacheMinutes > 0) {
            return Cache::remember('zkbiotime.auth_token', now()->addMinutes($cacheMinutes), function () {
                return $this->requestToken();
            });
        }

        return $this->requestToken();
    }

    protected function requestToken(): string
    {
        $username = $this->config['username'] ?? null;
        $password = $this->config['password'] ?? null;

        if (empty($username) || empty($password)) {
            throw new RuntimeException('ZKBioTime credentials are missing. Set ZKBIOTIME_USERNAME and ZKBIOTIME_PASSWORD.');
        }

        $url = rtrim($this->config['base_url'], '/') . '/api-token-auth/';
        $response = Http::timeout((int) ($this->config['timeout'] ?? 30))
            ->withOptions(['verify' => (bool) ($this->config['verify_ssl'] ?? true)])
            ->post($url, [
                'username' => $username,
                'password' => $password,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('ZKBioTime token request failed: ' . $response->status() . ' ' . $response->body());
        }

        $token = $response->json('token')
            ?? $response->json('data.token')
            ?? $response->json('access')
            ?? null;

        if (empty($token)) {
            throw new RuntimeException('ZKBioTime token response did not include a token.');
        }

        return $token;
    }

    protected function formatAuthHeader(string $token): string
    {
        $type = strtoupper((string) ($this->config['auth_type'] ?? 'TOKEN'));
        $type = $type === 'JWT' ? 'JWT' : 'Token';

        return $type . ' ' . $token;
    }
}
