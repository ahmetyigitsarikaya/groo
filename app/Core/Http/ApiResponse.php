<?php

declare(strict_types=1);

namespace App\Core\Http;

final class ApiResponse
{
    public function __construct(
        private bool $success,
        private $data = null,
        private ?string $message = null,
        private int $status = 200,
        private ?string $code = null,
        private array $meta = []
    ) {
    }

    public static function success($data = null, ?string $message = null, int $status = 200, array $meta = []): self
    {
        return new self(true, $data, $message, $status, null, $meta);
    }

    public static function error(string $message, int $status = 400, ?string $code = null, array $meta = []): self
    {
        return new self(false, null, $message, $status, $code, $meta);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->success ? null : [
                'code' => $this->code,
                'message' => $this->message,
            ],
            'message' => $this->message,
            'meta' => (object) $this->meta,
        ];
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
