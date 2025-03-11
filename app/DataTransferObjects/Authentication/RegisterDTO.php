<?php

namespace App\DataTransferObjects\Authentication;

class RegisterDTO
{
    public function __construct(
        public string $username,
        public string $password,
        public string $phone
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            username: $data['username'],
            password: $data['password'],
            phone: $data['phone']
        );
    }
}
