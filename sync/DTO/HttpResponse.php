<?php

declare(strict_types=1);

namespace BlackPrint\Suppliers\Http;

final class HttpResponse
{
    public function __construct(

        private readonly array $body,

        private readonly int $status,

        private readonly int $durationMs,

        private readonly array $headers = []

    ) {
    }

    public function body(): array
    {
        return $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function durationMs(): int
    {
        return $this->durationMs;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function successful(): bool
    {
        return $this->status >= 200
            && $this->status < 300;
    }
}