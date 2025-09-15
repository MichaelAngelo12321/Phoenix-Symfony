<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

final class PhoenixApiException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?array $errors = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public static function fromResponse(int $statusCode, array $data, string $context = ''): self
    {
        $message = $context ? "{$context}: " : '';
        $message .= "Phoenix API error: HTTP {$statusCode}";

        if (isset($data['error'])) {
            $message .= " - {$data['error']}";
        }

        return new self($message, $statusCode, $data['errors'] ?? null);
    }

    public static function connectionFailed(string $error): self
    {
        return new self("Connection to Phoenix API failed: {$error}");
    }

    public static function validationError(array $errors): self
    {
        return new self('Validation error', 422, $errors);
    }

    public static function notFound(string $resource, int $id): self
    {
        return new self("{$resource} with ID {$id} not found", 404);
    }
}
