<?php

namespace Tests\Unit;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Jobs\ProcessCollaboratorCsv;

class CollaboratorControllerUnitTest extends TestCase
{
    public function test_create_collaborator_with_valid_data()
    {
        // Cria um usuário para testar como gestor
        $user = User::factory()->create();

        // Garantir que o email e CPF sejam únicos
        $uniqueEmail = 'teste@teste6.com';
        $uniqueCpf = '43287609811';

        // Remove qualquer colaborador com o mesmo email ou CPF
        Collaborator::where('email', $uniqueEmail)->orWhere('cpf', $uniqueCpf)->delete();

        // Dados para um colaborador válido e não duplicado
        $newData = [
            'name' => 'Matheus',
            'email' => $uniqueEmail, // Novo email
            'cpf' => $uniqueCpf, // Novo CPF
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
        ];

        // Realiza a requisição POST com autenticação via token JWT
        $response = $this->actingAs($user, 'api')->postJson('/api/collaborators', $newData);

        // Verifica se o status é 201 (Criado com sucesso)
        $response->assertStatus(201)
                ->assertJsonFragment([
                    'message' => 'Collaborator created successfully',
                ]);

        // Verifica se o colaborador foi realmente criado no banco de dados
        $this->assertDatabaseHas('collaborators', [
            'email' => $uniqueEmail,
            'cpf' => $uniqueCpf,
        ]);
    }

    /**
     * Testa a criação de colaborador com dados duplicados.
     *
     * @return void
     */
    public function test_cannot_create_duplicate_collaborator()
    {
        // Cria um usuário para testar como gestor
        $user = User::factory()->create();

        // Verifica se já existe um colaborador com o email e CPF
        $existingCollaborator = Collaborator::where('email', 'joao.silva@example.com')
                                            ->orWhere('cpf', '12345678900')
                                            ->first();

        // Se não existir, cria um colaborador com o mesmo email e CPF
        if (!$existingCollaborator) {
            Collaborator::create([
                'name' => 'João Silva',
                'email' => 'joao.silva@example.com',
                'cpf' => '12345678900',
                'city' => 'São Paulo',
                'state' => 'SP',
                'user_id' => $user->id,
            ]);
        }

        // Dados de um colaborador com o mesmo email e CPF (duplicado)
        $data = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'cpf' => '12345678900',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];

        // Realiza a requisição POST com o token JWT
        $response = $this->actingAs($user, 'api')->postJson('/api/collaborators', $data);

        // Verifica se o status é 422 devido à duplicidade de email e CPF
        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'A collaborator with this email or CPF already exists.']);
    }


    /**
     * Testa se o colaborador pode ser deletado com sucesso.
     *
     * @return void
     */
    public function test_delete_collaborator_success()
    {
        // Cria um usuário para testar como gestor
        $user = User::factory()->create();

        // Cria um colaborador para ser deletado
        $collaborator = Collaborator::factory()->create([
            'user_id' => $user->id,
        ]);

        // Realiza a autenticação do usuário com o guard 'api'
        $this->actingAs($user, 'api');

        // Realiza a requisição DELETE para deletar o colaborador
        $response = $this->deleteJson("/api/collaborators/{$collaborator->id}");

        // Verifica se o status é 200 (Deletado com sucesso)
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Collaborator deleted successfully',
                 ]);

        // Verifica se o colaborador foi realmente deletado no banco de dados
        $this->assertDatabaseMissing('collaborators', [
            'id' => $collaborator->id,
        ]);
    }

    /**
     * Testa a listagem de colaboradores para o usuário logado.
     *
     * @return void
     */
    public function test_index_returns_collaborators_for_logged_in_user()
    {
        // Cria um usuário para testar
        $user = User::factory()->create();

        // Simula a autenticação do usuário com o guard 'api'
        $this->actingAs($user, 'api');

        // Cria colaboradores associados ao usuário logado
        $collaborators = Collaborator::factory()->count(3)->create(['user_id' => $user->id]);

        // Realiza a requisição GET para listar os colaboradores
        $response = $this->getJson('/api/collaborators');

        // Verifica se o status é 200 (OK)
        $response->assertStatus(200);

        // Verifica se os colaboradores retornados correspondem ao que foi criado
        $response->assertJsonCount(3, 'data'); // Verifica se a quantidade de colaboradores é 3

        // Verifica se o JSON contém os dados dos colaboradores
        $response->assertJsonFragment([
            'name' => $collaborators[0]->name,
            'email' => $collaborators[0]->email,
        ]);
    }
}
