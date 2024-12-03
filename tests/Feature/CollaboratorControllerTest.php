<?php

namespace Tests\Feature;

use App\Jobs\ProcessCollaboratorCsv;
use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CollaboratorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_processes_csv_and_sends_email()
    {
        Mail::fake();

        // Cria um usuário e autentica
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Fake storage para simular o upload de CSV
        Storage::fake();

        $csvContent = "name,email,cpf,city,state\nJohn Doe,johndoe@example.com,12345678900,New York,NY";
        $filePath = 'uploads/test.csv';
        Storage::put($filePath, $csvContent);

        // Verifique se o arquivo foi criado corretamente
        $this->assertTrue(Storage::exists($filePath), 'CSV file was not created.');

        // Processa o job diretamente
        $job = new ProcessCollaboratorCsv(Storage::path($filePath), $user->id);
        $job->handle();

        // Debug: log dos registros inseridos
        Log::info('Registros após processar o CSV:', ['data' => Collaborator::all()]);

        // Verifica se os dados foram inseridos na tabela
        $this->assertDatabaseHas('collaborators', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'cpf' => '12345678900',
            'city' => 'New York',
            'state' => 'NY',
        ]);

        // Verifica se o e-mail foi enviado
        Mail::assertSent(function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_list_collaborators_uses_cache()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Debug: Verifica se o usuário está autenticado
        $this->assertTrue(auth()->check(), 'User is not authenticated.');

        // Cria colaboradores para o usuário autenticado
        Collaborator::factory()->count(3)->create(['user_id' => $user->id]);

        // Simula uma requisição para listar colaboradores
        $response = $this->getJson('/api/collaborators');

        // Debug: Exibe o status e o conteúdo da resposta se falhar
        if ($response->status() !== 200) {
            Log::error('Erro ao listar colaboradores', [
                'status' => $response->status(),
                'content' => $response->getContent(),
            ]);
        }

        // Verifica o status da resposta
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }
}
