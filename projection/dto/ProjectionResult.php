<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Projection\DTO;

defined('ABSPATH') || exit;

/**
 * Immutable result of a product projection operation.
 *
 * The result describes what the projector determined should happen.
 * It does not itself perform any WooCommerce mutation.
 */
final class ProjectionResult
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        private readonly bool $success,
        private readonly ?int $productId,
        private readonly string $action,
        private readonly ?string $message = null,
        private readonly array $data = []
    ) {
    }

    /**
     * Create a successful projection plan.
     *
     * No WooCommerce mutation has occurred.
     *
     * @param array<string, mixed> $data
     */
    public static function planned(
        array $data = []
    ): self {
        return new self(
            success: true,
            productId: null,
            action: 'planned',
            data: $data
        );
    }

    /**
     * Create a successful "created" result.
     *
     * Used by the future WooCommerce writer.
     *
     * @param array<string, mixed> $data
     */
    public static function created(
        int $productId,
        array $data = []
    ): self {
        return new self(
            success: true,
            productId: $productId,
            action: 'created',
            data: $data
        );
    }

    /**
     * Create a successful "updated" result.
     *
     * Used by the future WooCommerce writer.
     *
     * @param array<string, mixed> $data
     */
    public static function updated(
        int $productId,
        array $data = []
    ): self {
        return new self(
            success: true,
            productId: $productId,
            action: 'updated',
            data: $data
        );
    }

    /**
     * Create a successful result where no action is required.
     *
     * @param array<string, mixed> $data
     */
    public static function skipped(
        string $message,
        array $data = []
    ): self {
        return new self(
            success: true,
            productId: null,
            action: 'skipped',
            message: $message,
            data: $data
        );
    }

    /**
     * Create a failed projection result.
     *
     * @param array<string, mixed> $data
     */
    public static function failed(
        string $message,
        array $data = []
    ): self {
        return new self(
            success: false,
            productId: null,
            action: 'failed',
            message: $message,
            data: $data
        );
    }

    /**
     * Determine whether the projection operation succeeded.
     */
    public function success(): bool
    {
        return $this->success;
    }

    /**
     * Return the WooCommerce product ID, when one exists.
     */
    public function productId(): ?int
    {
        return $this->productId;
    }

    /**
     * Return the projection action.
     */
    public function action(): string
    {
        return $this->action;
    }

    /**
     * Return the result message.
     */
    public function message(): ?string
    {
        return $this->message;
    }

    /**
     * Return structured projection data.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}
