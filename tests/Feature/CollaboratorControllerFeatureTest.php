<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessCollaboratorCsv;
use Illuminate\Support\Facades\Storage;

class CollaboratorControllerFeatureTest extends TestCase
{
    /**
     * Testa a criação de colaborador com dados duplicados.
     *
     * @return void
     */
    public function test_cannot_create_duplicate_collaborator()
    {
        // Cria um usuário para testar como gestor
        $user = User::factory()->create();

        // Realiza a autenticação do usuário com o guard 'api'
        $this->actingAs($user, 'api');  // Usando actingAs() para simular o login com o token

        // Verifica se já existe um colaborador com o email e CPF
        $existingCollaborator = Collaborator::where('email', 'joao.silva@example.com')
                                            ->orWhere('cpf', '12345678900')
                                            ->first();

        // Se não existir, cria um colaborador com o mesmo email e CPF
        if (!$existingCollaborator) {
            Collaborator::create([
                'name' => 'João Silva',
                'email' => 'joao.silva@example.com', // Esse email
                'cpf' => '12345678900', // Esse CPF
                'city' => 'São Paulo',
                'state' => 'SP',
                'user_id' => $user->id,
            ]);
        }

        // Dados de um colaborador com o mesmo email e CPF (duplicado)
        $data = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com', // Esse email já existe
            'cpf' => '12345678900', // Esse CPF já existe
            'city' => 'São Paulo',
            'state' => 'SP',
        ];

        // Realiza a requisição POST com o token JWT
        $response = $this->postJson('/api/collaborators', $data);

        // Verifica se o status é 422 devido à duplicidade de email e CPF
        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'A collaborator with this email or CPF already exists.']);
    }

    /**
     * Testa a criação de colaborador com dados válidos (não duplicados).
     *
     * @return void
     */
    public function test_create_collaborator_with_valid_data()
    {
        // Cria um usuário para testar como gestor
        $user = User::factory()->create();

        // Realiza a autenticação do usuário com o guard 'api'
        $this->actingAs($user, 'api');  // Usando actingAs() para simular o login com o token

        // Remove qualquer colaborador com o mesmo email ou CPF
        Collaborator::where('email', 'teste@teste6.com')->orWhere('cpf', '43287609811')->delete();

        // Dados de um colaborador válido e não duplicado
        $newData = [
            'name' => 'Matheus',
            'email' => 'teste@teste6.com', // Novo email
            'cpf' => '43287609811', // Novo CPF
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
        ];

        // Realiza a requisição POST com autenticação via token JWT
        $newResponse = $this->postJson('/api/collaborators', $newData);

        // Verifica se o status é 201 (Criado com sucesso)
        $newResponse->assertStatus(201)
                    ->assertJsonFragment([
                        'message' => 'Collaborator created successfully',
                    ]);

        // Verifica se o colaborador foi realmente criado no banco de dados
        $this->assertDatabaseHas('collaborators', [
            'email' => 'teste@teste6.com',
            'cpf' => '43287609811',
        ]);

        // Verifica todos os campos retornados na resposta
        $newResponse->assertJsonFragment([
            'message' => 'Collaborator created successfully',
        ]);
        
        // Verifica a estrutura de 'collaborator' na resposta JSON
        $newResponse->assertJson([
            'collaborator' => [
                'name' => 'Matheus',
                'email' => 'teste@teste6.com',
                'cpf' => '43287609811',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'user_id' => $user->id,  // Verificando o user_id gerado automaticamente
                'id' => true,             // O ID deve ser gerado e estar presente
                'created_at' => true,     // O created_at deve estar presente
                'updated_at' => true,     // O updated_at deve estar presente
            ]
        ]);

        // Verifica se os campos id, created_at e updated_at estão presentes e têm valores
        $responseData = $newResponse->json();
        $collaborator = $responseData['collaborator'];

        // Verifica se 'id', 'created_at' e 'updated_at' estão presentes e não são nulos
        $this->assertNotNull($collaborator['id']);
        $this->assertNotNull($collaborator['created_at']);
        $this->assertNotNull($collaborator['updated_at']);
    }

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

        // Verifica se o cache está sendo usado
        Cache::shouldReceive('remember')
            ->once()
            ->with("collaborators_user_{$user->id}", 600, \Closure::class)
            ->andReturn($collaborators);

        // Verifica se os colaboradores são retornados do cache ou banco de dados
        $this->getJson('/api/collaborators');
    }

    /**
     * Testa se retorna erro quando o colaborador não for encontrado.
     *
     * @return void
     */
    public function test_update_collaborator_not_found()
    {
        // Cria um usuário para testar como gestor
        $user = User::factory()->create();

        // Realiza a autenticação do usuário com o guard 'api'
        $this->actingAs($user, 'api');

        // Dados para atualizar o colaborador
        $updateData = [
            'name' => 'Carlos Silva',
            'email' => 'carlos.silva@example.com',
            'cpf' => '98765432100',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];

        // Realiza a requisição PUT com um ID que não existe
        $response = $this->putJson('/api/collaborators/99999', $updateData);

        // Verifica se o status é 404 (Colaborador não encontrado)
        $response->assertStatus(404)
                ->assertJsonFragment([
                    'message' => 'Collaborator not found',
                ]);
    }

    /**
     * Testa se retorna erro de permissão quando o usuário não tem permissão para atualizar o colaborador.
     *
     * @return void
     */
    public function test_update_collaborator_permission_denied()
    {
        // Cria dois usuários: um para testar como gestor e outro para tentar atualizar o colaborador
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Cria um colaborador com o usuário 1
        $collaborator = Collaborator::factory()->create([
            'user_id' => $user1->id,  // Colaborador pertence ao usuário 1
        ]);

        // Realiza a autenticação do usuário 2 (que não tem permissão)
        $this->actingAs($user2, 'api');

        // Dados para atualizar o colaborador
        $updateData = [
            'name' => 'Carlos Silva',
            'email' => 'carlos.silva@example.com',
            'cpf' => '98765432100',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];

        // Realiza a requisição PUT com um ID de colaborador que o usuário não tem permissão para editar
        $response = $this->putJson("/api/collaborators/{$collaborator->id}", $updateData);

        // Verifica se o status é 403 (Acesso não autorizado)
        $response->assertStatus(403)
                ->assertJsonFragment([
                    'message' => 'This action is unauthorized.',
                ]);
    }

    /**
     * Testa se o colaborador pode ser excluído com sucesso.
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
        $this->actingAs($user, 'api');  // Usando actingAs() para simular o login com o token

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
     * Testa se retorna erro quando o colaborador não for encontrado.
     *
     * @return void
     */
    public function test_delete_collaborator_not_found()
    {
        // Cria um usuário para testar como gestor
        $user = User::factory()->create();

        // Realiza a autenticação do usuário com o guard 'api'
        $this->actingAs($user, 'api');

        // Realiza a requisição DELETE com um ID que não existe
        $response = $this->deleteJson('/api/collaborators/99999');

        // Verifica se o status é 404 (Colaborador não encontrado)
        $response->assertStatus(404)
                 ->assertJsonFragment([
                     'message' => 'Collaborator not found',
                 ]);
    }

    /**
     * Testa se retorna erro de permissão quando o usuário não tem permissão para deletar o colaborador.
     *
     * @return void
     */
    public function test_delete_collaborator_permission_denied()
    {
        // Cria dois usuários: um para testar como gestor e outro para tentar deletar o colaborador
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Cria um colaborador com o usuário 1
        $collaborator = Collaborator::factory()->create([
            'user_id' => $user1->id,
        ]);

        // Realiza a autenticação do usuário 2 (que não tem permissão)
        $this->actingAs($user2, 'api');

        // Realiza a requisição DELETE com um ID de colaborador que o usuário não tem permissão para excluir
        $response = $this->deleteJson("/api/collaborators/{$collaborator->id}");

        // Verifica se o status é 403 (Acesso não autorizado)
        $response->assertStatus(403)
                 ->assertJsonFragment([
                     'message' => 'This action is unauthorized.',
                 ]);
    }
}
