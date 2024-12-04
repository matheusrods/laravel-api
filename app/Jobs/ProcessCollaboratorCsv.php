<?php

namespace App\Jobs;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\CollaboratorProcessed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProcessCollaboratorCsv implements ShouldQueue, \Illuminate\Contracts\Queue\ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $filePath;
    private int $userId;
    public int $uniqueFor = 3600; // Garantia de unicidade do job por 1 hora

    public function __construct(string $filePath, int $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    public function handle()
    {
        $processedCount = 0;
        $failedCount = 0;
        $duplicatedCount = 0;
        $failedRecords = [];

        $user = User::find($this->userId);

        if (!$user) {
            Log::error("Usuário não encontrado para o processamento.", ['user_id' => $this->userId]);
            return;
        }

        try {
            // Verifica e obtém o caminho do arquivo
            $path = Storage::path($this->filePath);
            Log::info('Iniciando processamento do CSV', ['path' => $path]);

            if (!file_exists($path)) {
                throw new \Exception('Arquivo CSV não encontrado.');
            }

            $rows = array_map('str_getcsv', file($path));
            $header = array_shift($rows);

            // Valida o cabeçalho do CSV
            if (!$this->validateHeader($header)) {
                throw new \Exception('Formato do cabeçalho do CSV inválido.');
            }

            // Processa cada linha do CSV
            foreach ($rows as $row) {
                try {
                    $data = $this->validateRow($header, $row);

                    // Validações adicionais
                    $validator = Validator::make($data, [
                        'name' => 'required|string|max:255',
                        'email' => 'required|email|unique:collaborators,email',
                        'cpf' => 'required|digits:11|unique:collaborators,cpf',
                        'city' => 'required|string|max:255',
                        'state' => 'required|string|max:2',
                    ]);

                    if ($validator->fails()) {
                        $failedCount++;
                        $failedRecords[] = ['data' => $data, 'errors' => $validator->errors()];
                        Log::warning('Dados inválidos', ['data' => $data, 'errors' => $validator->errors()]);
                        continue;
                    }

                    // Verifica duplicatas
                    if (Collaborator::where('email', $data['email'])->exists()) {
                        $duplicatedCount++;
                        Log::warning("Colaborador já existente.", ['email' => $data['email']]);
                        continue;
                    }

                    // Criação ou atualização de colaborador
                    Collaborator::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'cpf' => $data['cpf'],
                        'city' => $data['city'],
                        'state' => $data['state'],
                        'user_id' => $this->userId,
                    ]);

                    $processedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Erro ao processar linha do CSV", [
                        'linha' => $row,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }

            // Limpa o cache
            cache()->forget("collaborators-{$this->userId}");

            // Envio do e-mail ao usuário
            Mail::to($user->email)->send(new CollaboratorProcessed([
                'total_processed' => $processedCount,
                'total_failed' => $failedCount,
                'duplicated_count' => $duplicatedCount,
                'failures' => $failedRecords,
                'timestamp' => now()->toDateTimeString(),
            ]));

            Log::info('E-mail enviado com sucesso.', ['email' => $user->email]);

        } catch (\Exception $e) {
            Log::error("Erro no processamento do arquivo CSV: {$e->getMessage()}");

            // Envia e-mail de erro ao usuário
            Mail::to($user->email)->send(new CollaboratorProcessed([
                'error' => 'Houve um erro no processamento. Por favor, tente novamente mais tarde.',
                'timestamp' => now()->toDateTimeString(),
            ]));
        } finally {
            // Garante que o arquivo será removido
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
                Log::info('Arquivo CSV deletado com sucesso.', ['path' => $this->filePath]);
            }
        }
    }

    private function validateHeader(array $header): bool
    {
        $expectedHeader = ['name', 'email', 'cpf', 'city', 'state'];
        return empty(array_diff($expectedHeader, $header));
    }

    private function validateRow(array $header, array $row): array
    {
        if (count($header) !== count($row)) {
            throw new \Exception('Linha do CSV com número de colunas inválido.');
        }

        return array_combine($header, $row);
    }
}