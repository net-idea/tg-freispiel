<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Transport-agnostic result of a form submission. The controller
 * decides how to present it (JSON for Ajax, rendered page as fallback).
 */
final readonly class SubmissionResult
{
    private function __construct(
        public SubmissionStatus $status,
    ) {
    }

    public static function success(): self
    {
        return new self(SubmissionStatus::SUCCESS);
    }

    public static function invalid(): self
    {
        return new self(SubmissionStatus::INVALID);
    }

    public static function spam(): self
    {
        return new self(SubmissionStatus::SPAM);
    }

    public static function rateLimited(): self
    {
        return new self(SubmissionStatus::RATE_LIMITED);
    }

    public static function mailError(): self
    {
        return new self(SubmissionStatus::MAIL_ERROR);
    }

    /**
     * Spam submissions are presented as success so bots learn nothing.
     */
    public function shouldPresentAsSuccess(): bool
    {
        return SubmissionStatus::SUCCESS === $this->status || SubmissionStatus::SPAM === $this->status;
    }
}
