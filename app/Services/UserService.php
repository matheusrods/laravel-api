<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    private $repository;

    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $data)
    {
        // Verifica se o usuário já existe pelo email
        $existingUser = $this->repository->findByEmail($data['email']);
        if ($existingUser) {
            throw new \Exception('A user with this email already exists.');
        }

        // Cria o usuário
        $data['password'] = Hash::make($data['password']);
        $user = $this->repository->create($data);

        // Limpa o cache da lista de usuários
        Cache::forget('users-list');

        return $user;
    }


    public function getAll()
    {
        return Cache::remember('users-list', 600, function () {
            return $this->repository->getAll();
        });
    }
}