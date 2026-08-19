<?php

namespace App\Services;

use App\Models\Pilot;
use Illuminate\Support\Str;
use RuntimeException;

class JwtTokenService
{
    public function issue(Pilot $pilot): string
    {
        $now = now()->timestamp;
        $payload = [
            'sub' => (string) $pilot->getAuthIdentifier(),
            'iat' => $now,
            'exp' => $now + (config('oryxpace.jwt_ttl_minutes') * 60),
            'jti' => (string) Str::uuid(),
        ];

        $header = $this->encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $body = $this->encode($payload);
        $signature = hash_hmac('sha256', "$header.$body", $this->secret(), true);

        return "$header.$body." . $this->base64UrlEncode($signature);
    }

    public function pilotFrom(string $token): ?Pilot
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', "$header.$body", $this->secret(), true));

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($body), true);

        if (!is_array($payload) || !isset($payload['sub'], $payload['exp']) || $payload['exp'] < now()->timestamp) {
            return null;
        }

        $pilot = Pilot::find((int) $payload['sub']);

        return $pilot?->isActive() ? $pilot : null;
    }

    private function secret(): string
    {
        $secret = (string) config('oryxpace.jwt_secret');

        if ($secret === '') {
            throw new RuntimeException('AUTH_JWT_SECRET or APP_KEY must be configured.');
        }

        return $secret;
    }

    private function encode(array $value): string
    {
        return $this->base64UrlEncode(json_encode($value, JSON_THROW_ON_ERROR));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value . str_repeat('=', (4 - strlen($value) % 4) % 4), '-_', '+/')) ?: '';
    }
}
