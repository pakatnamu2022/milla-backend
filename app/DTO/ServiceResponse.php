<?php

namespace App\DTO;

class ServiceResponse
{
    public function __construct(
        public mixed   $data = null,
        public int     $status = 500,
        public ?string $message = null,
    )
    {
    }

    public static function success(mixed $data): self
    {
        return new self($data, 200);
    }

    public static function error(string $message, int $status = 500): self
    {
        return new self(null, $status, $message);
    }

}
