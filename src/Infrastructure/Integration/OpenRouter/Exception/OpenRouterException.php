<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\Exception;

use RuntimeException;

/**
 * Base exception for all OpenRouter-related errors.
 * Extends RuntimeException to indicate errors that occur during runtime.
 */
class OpenRouterException extends \RuntimeException
{
}
