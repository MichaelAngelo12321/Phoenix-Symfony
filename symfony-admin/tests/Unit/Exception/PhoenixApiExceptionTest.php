<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\PhoenixApiException;
use Exception;
use PHPUnit\Framework\TestCase;

final class PhoenixApiExceptionTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $message = 'Test error message';
        $statusCode = 422;
        $errors = ['field' => 'error message'];
        $previous = new Exception('Previous exception');

        $exception = new PhoenixApiException($message, $statusCode, $errors, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($statusCode, $exception->getStatusCode());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $message = 'Simple error';

        $exception = new PhoenixApiException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getStatusCode());
        $this->assertNull($exception->getErrors());
        $this->assertNull($exception->getPrevious());
    }

    public function testFromResponseWithFullData(): void
    {
        $statusCode = 422;
        $data = [
            'error' => 'Validation failed',
            'errors' => ['name' => 'is required'],
        ];
        $context = 'User creation';

        $exception = PhoenixApiException::fromResponse($statusCode, $data, $context);

        $this->assertEquals('User creation: Phoenix API error: HTTP 422 - Validation failed', $exception->getMessage());
        $this->assertEquals($statusCode, $exception->getStatusCode());
        $this->assertEquals($data['errors'], $exception->getErrors());
    }

    public function testFromResponseWithoutContext(): void
    {
        $statusCode = 500;
        $data = ['error' => 'Internal server error'];

        $exception = PhoenixApiException::fromResponse($statusCode, $data);

        $this->assertEquals('Phoenix API error: HTTP 500 - Internal server error', $exception->getMessage());
        $this->assertEquals($statusCode, $exception->getStatusCode());
        $this->assertNull($exception->getErrors());
    }

    public function testFromResponseWithoutErrorMessage(): void
    {
        $statusCode = 404;
        $data = [];

        $exception = PhoenixApiException::fromResponse($statusCode, $data);

        $this->assertEquals('Phoenix API error: HTTP 404', $exception->getMessage());
        $this->assertEquals($statusCode, $exception->getStatusCode());
        $this->assertNull($exception->getErrors());
    }

    public function testFromResponseWithErrorsButNoErrorMessage(): void
    {
        $statusCode = 422;
        $data = ['errors' => ['field1' => 'error1', 'field2' => 'error2']];

        $exception = PhoenixApiException::fromResponse($statusCode, $data);

        $this->assertEquals('Phoenix API error: HTTP 422', $exception->getMessage());
        $this->assertEquals($statusCode, $exception->getStatusCode());
        $this->assertEquals($data['errors'], $exception->getErrors());
    }

    public function testConnectionFailed(): void
    {
        $error = 'Connection timeout';

        $exception = PhoenixApiException::connectionFailed($error);

        $this->assertEquals('Connection to Phoenix API failed: Connection timeout', $exception->getMessage());
        $this->assertEquals(0, $exception->getStatusCode());
        $this->assertNull($exception->getErrors());
    }

    public function testValidationError(): void
    {
        $errors = [
            'email' => 'is required',
            'password' => 'is too short',
        ];

        $exception = PhoenixApiException::validationError($errors);

        $this->assertEquals('Validation error', $exception->getMessage());
        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals($errors, $exception->getErrors());
    }

    public function testNotFound(): void
    {
        $resource = 'User';
        $id = 123;

        $exception = PhoenixApiException::notFound($resource, $id);

        $this->assertEquals('User with ID 123 not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertNull($exception->getErrors());
    }

    public function testInheritanceFromException(): void
    {
        $exception = new PhoenixApiException('Test message');

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testGetStatusCodeMethod(): void
    {
        $statusCode = 403;
        $exception = new PhoenixApiException('Forbidden', $statusCode);

        $this->assertEquals($statusCode, $exception->getStatusCode());
    }

    public function testGetErrorsMethod(): void
    {
        $errors = ['field' => 'validation error'];
        $exception = new PhoenixApiException('Error', 422, $errors);

        $this->assertEquals($errors, $exception->getErrors());
    }

    public function testGetErrorsMethodWithNullErrors(): void
    {
        $exception = new PhoenixApiException('Error');

        $this->assertNull($exception->getErrors());
    }
}
