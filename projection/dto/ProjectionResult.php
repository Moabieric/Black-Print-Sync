<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\DTO;

defined('ABSPATH') || exit;

/**
 * Immutable result of a product projection operation.
 */
final class ProjectionResult
{
    private function __construct(
        private readonly bool $success,
        private readonly ?int $productId,
        private readonly string $action,
        private readonly ?string $message = null
    ) {
    }

    public static function created(
        int $productId
    ): self {
        return new self(
            success: true,
            productId: $productId,
            action: 'created'
        );
    }

    public static function updated(
        int $productId
    ): self {
        return new self(
            success: true,
            productId: $productId,
            action: 'updated'
        );
    }

    public static function skipped(
        string $message
    ): self {
        return new self(
            success: true,
            productId: null,
            action: 'skipped',
            message: $message
        );
    }

    public static function failed(
        string $message
    ): self {
        return new self(
            success: false,
            productId: null,
            action: 'failed',
            message: $message
        );
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function productId(): ?int
    {
        return $this->productId;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}