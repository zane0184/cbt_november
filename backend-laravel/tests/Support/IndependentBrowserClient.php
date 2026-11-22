<?php

namespace Tests\Support;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Small in-process HTTP client with an isolated cookie jar.
 *
 * Each instance represents a separate browser. It sends the cookies received
 * from its own prior responses back through the real Laravel HTTP kernel.
 * Auth guards are reset only to model the server's per-request lifecycle;
 * authentication itself must still be reconstructed from this client's cookies.
 */
final class IndependentBrowserClient
{
    /** @var array<string, string> */
    private array $cookies = [];

    public function request(string $method, string $uri, array $input = [], array $headers = []): Response
    {
        Auth::forgetGuards();

        $url = 'http://cbt.test' . '/' . ltrim($uri, '/');
        $request = Request::create($url, strtoupper($method), $input, $this->cookies);

        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        $kernel = app(Kernel::class);
        $response = $kernel->handle($request);

        foreach ($response->headers->getCookies() as $cookie) {
            $expiresAt = $cookie->getExpiresTime();
            if ($expiresAt > 0 && $expiresAt <= time()) {
                unset($this->cookies[$cookie->getName()]);
                continue;
            }

            $this->cookies[$cookie->getName()] = $cookie->getValue();
        }

        $kernel->terminate($request, $response);
        Auth::forgetGuards();

        return $response;
    }

    public function cookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function setCookie(string $name, string $value): void
    {
        $this->cookies[$name] = $value;
    }

    public function removeCookie(string $name): void
    {
        unset($this->cookies[$name]);
    }

    /** @return array<string, string> */
    public function cookies(): array
    {
        return $this->cookies;
    }
}
