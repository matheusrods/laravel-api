<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{

    public function test_login()
    {
        // Limpar o usuário com o email 'testuser@example.com' antes de criar um novo
        User::where('email', 'testuser@example.com')->delete();

        // Cria um usuário para testar o login
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Realiza a requisição POST para login com o email e senha
        $response = $this->postJson('/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'password123',
        ]);

        // Verifica se o status é 200 (Token gerado com sucesso)
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token',
                    'token_type',
                    'expires_in',
                ]);
    }

    public function test_logout()
    {
        // Limpa o usuário antes de criar um novo
        User::where('email', 'testuser@example.com')->delete();

        // Cria um usuário para testar o logout
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Gera um token para o usuário
        $token = JWTAuth::fromUser($user);

        // Realiza a requisição POST para logout com o token JWT
        $response = $this->withHeader('Authorization', "Bearer $token")
                        ->postJson('/api/logout');

        // Verifica se o status é 200 (Logout bem-sucedido)
        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Successfully logged out',
                ]);
    }
}