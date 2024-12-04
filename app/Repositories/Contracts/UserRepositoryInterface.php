<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data): User;
    public function getAll(): \Illuminate\Support\Collection;
    public function findByEmail(string $email);
}

