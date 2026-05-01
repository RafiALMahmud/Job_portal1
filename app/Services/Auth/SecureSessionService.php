<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserSession;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class SecureSessionService
{
    public function createSession(User $user, Request $request): array
    {
        $token = $this->generateToken();

        $session = UserSession::create([
            'user_id' => $user->id,
            'session_token_hash' => $this->hashToken($token),
            'ip_address' => $request->ip(),
            'user_agent' => $this->normalizeUserAgent($request->userAgent()),
            'expires_at' => now()->addMinutes($this->lifetimeMinutes()),
            'revoked' => false,
        ]);

        return [
            'token' => $token,
            'session' => $session,
        ];
    }

    public function resolveSessionFromRequest(Request $request): ?UserSession
    {
        $token = $this->extractBearerToken($request);
        if (!$token) {
            return null;
        }

        $session = UserSession::with('user')
            ->where('session_token_hash', $this->hashToken($token))
            ->first();

        if (!$session) {
            return null;
        }

        if (!$session->user) {
            return null;
        }

        if ($session->revoked || $session->expires_at->isPast()) {
            return null;
        }

        if ($this->bindIp() && $session->ip_address && $session->ip_address !== $request->ip()) {
            return null;
        }

        if ($this->bindUserAgent()) {
            $requestAgent = $this->normalizeUserAgent($request->userAgent());

            if ($session->user_agent && $session->user_agent !== $requestAgent) {
                return null;
            }
        }

        return $session;
    }

    public function extractBearerToken(Request $request): ?string
    {
        $header = trim((string) $request->header('Authorization', ''));
        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        $token = trim($matches[1]);

        return $token !== '' ? $token : null;
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function revokeSession(UserSession $session): void
    {
        if (!$session->revoked) {
            $session->forceFill(['revoked' => true])->save();
        }
    }

    public function revokeAllSessionsForUser(User $user): int
    {
        return UserSession::where('user_id', $user->id)
            ->where('revoked', false)
            ->update(['revoked' => true, 'updated_at' => now()]);
    }

    public function makeCookie(string $token, CarbonInterface $expiresAt): SymfonyCookie
    {
        $minutes = max(1, now()->diffInMinutes($expiresAt, false));

        return Cookie::make(
            $this->cookieName(),
            $token,
            $minutes,
            '/',
            null,
            $this->cookieSecure(),
            true,
            false,
            $this->sameSite()
        );
    }

    public function forgetCookie(): SymfonyCookie
    {
        return Cookie::forget($this->cookieName(), '/');
    }

    public function cookieName(): string
    {
        return (string) config('secure_session.cookie_name', 'hirely_session_token');
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function normalizeUserAgent(?string $userAgent): ?string
    {
        $normalized = trim((string) $userAgent);

        return $normalized !== '' ? mb_substr($normalized, 0, 1000) : null;
    }

    private function lifetimeMinutes(): int
    {
        return max(1, (int) config('secure_session.lifetime_minutes', 1440));
    }

    private function bindIp(): bool
    {
        return (bool) config('secure_session.bind_ip', true);
    }

    private function bindUserAgent(): bool
    {
        return (bool) config('secure_session.bind_user_agent', true);
    }

    private function cookieSecure(): bool
    {
        return (bool) config('secure_session.cookie_secure', false);
    }

    private function sameSite(): ?string
    {
        $sameSite = (string) config('secure_session.same_site', 'lax');

        return $sameSite !== '' ? $sameSite : null;
    }
}
