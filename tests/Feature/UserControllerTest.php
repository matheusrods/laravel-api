<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserControllerTest extends TestCase
{
     /**
     * Testa a criação de um usuário com dados válidos.
     *
     * @return void
     */
    public function test_create_user_with_valid_data()
    {
        // Cria um usuário para autenticar
        $user = User::factory()->create();

        // Gerar um email único para garantir que não seja duplicado
        $uniqueEmail = 'user' . Str::random(10) . '@example.com';

        // Dados válidos para criação do usuário
        $data = [
            'name' => 'John Doe',
            'email' => $uniqueEmail,
            'password' => 'password123',
        ];

        // Realiza a autenticação do usuário com o guard 'api'
        $this->actingAs($user, 'api');  // Usando actingAs() para autenticar com o token

        // Realiza a requisição POST para criar o usuário
        $response = $this->postJson('/api/users', $data);

        // Verifica se o status da resposta é 201 (Criado com sucesso)
        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'message' => 'User created successfully',
                 ]);

        // Verifica se o usuário foi realmente criado no banco de dados
        $this->assertDatabaseHas('users', [
            'email' => $uniqueEmail,
        ]);

        // Verifica se o usuário foi criado com o password hasheado
        $user = User::where('email', $uniqueEmail)->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Testa a listagem de usuários.
     *
     * @return void
     */
    public function test_list_users()
    {
        // Cria um usuário para testar
        $user = User::factory()->create();

        // Simula a autenticação do usuário com o guard 'api'
        $this->actingAs($user, 'api');

        // Realiza a requisição GET para listar os usuários
        $response = $this->getJson('/api/users');

        // Verifica se o status da resposta é 200
        $response->assertStatus(200);

        // Verifica se a resposta contém o usuário criado
        $response->assertJsonFragment([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}