<?php

declare(strict_types=1);

namespace BlackPrint\Suppliers\Amrod;

final class AmrodConfig
{
    public function __construct(

        private readonly string $baseUrl,

        private readonly string $username,

        private readonly string $password,

        private readonly int $timeout = 30,

        private readonly int $retries = 3

    ) {
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }

    public function retries(): int
    {
        return $this->retries;
    }
}