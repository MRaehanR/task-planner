<?php

namespace App\DataTransferObjects\Authentication;

class LoginDTO
{
    public function __construct(
        public string $username,
        public string $password
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            username: $data['username'],
            password: $data['password']
        );
    }
}