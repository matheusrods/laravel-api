<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function getAll(): \Illuminate\Support\Collection
    {
        return $this->model->all();
    }

    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }
}
