<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Transport-agnostic result of a registration submission. The controller
 * decides how to present it (JSON for Ajax, rendered page as fallback).
 */
final readonly class RegistrationResult
{
    private function __construct(
        public RegistrationStatus $status,
    ) {
    }

    public static function success(): self
    {
        return new self(RegistrationStatus::SUCCESS);
    }

    public static function invalid(): self
    {
        return new self(RegistrationStatus::INVALID);
    }

    public static function spam(): self
    {
        return new self(RegistrationStatus::SPAM);
    }

    public static function rateLimited(): self
    {
        return new self(RegistrationStatus::RATE_LIMITED);
    }

    public static function mailError(): self
    {
        return new self(RegistrationStatus::MAIL_ERROR);
    }

    /**
     * Spam submissions are presented as success so bots learn nothing.
     */
    public function shouldPresentAsSuccess(): bool
    {
        return RegistrationStatus::SUCCESS === $this->status || RegistrationStatus::SPAM === $this->status;
    }
}
