<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Outcome of handling an Anmeldung (registration) submission.
 */
enum RegistrationStatus: string
{
    case SUCCESS = 'success';
    case INVALID = 'invalid';
    case SPAM = 'spam';
    case RATE_LIMITED = 'rate_limited';
    case MAIL_ERROR = 'mail_error';
}
