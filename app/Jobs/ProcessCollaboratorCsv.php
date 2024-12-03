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

class ProcessCollaboratorCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $filePath;
    private $userId;

    public function __construct(string $filePath, int $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    public function handle()
    {
        $processedCount = 0;
        $failedCount = 0;

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

                    if (Collaborator::where('email', $data['email'])->exists()) {
                        Log::warning("Colaborador já existente.", ['email' => $data['email']]);
                        continue;
                    }

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

            // Envio do e-mail ao usuário
            $userEmail = User::find($this->userId)?->email;
            if (!$userEmail) {
                throw new \Exception('E-mail do usuário não encontrado.');
            }

            Mail::to($userEmail)->send(new CollaboratorProcessed([
                'total_processed' => $processedCount,
                'total_failed' => $failedCount,
                'duplicated_count' => $duplicatedCount ?? 0,
                'timestamp' => now()->toDateTimeString(),
            ]));

            Log::info('E-mail enviado com sucesso.', ['email' => $userEmail]);

            // Remove o arquivo após o processamento
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
                Log::info('Arquivo CSV deletado com sucesso.', ['path' => $this->filePath]);
            }
        } catch (\Exception $e) {
            Log::error("Erro no processamento do arquivo CSV: {$e->getMessage()}");

            // Envia e-mail de erro ao usuário
            $userEmail = User::find($this->userId)?->email;
            if ($userEmail) {
                Mail::to($userEmail)->send(new CollaboratorProcessed([
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toDateTimeString(),
                ]));
            }
        }
    }

    private function validateHeader(array $header): bool
    {
        $expectedHeader = ['name', 'email', 'cpf', 'city', 'state'];
        sort($header);
        sort($expectedHeader);
        return $header === $expectedHeader;
    }

    private function validateRow(array $header, array $row): array
    {
        if (count($header) !== count($row)) {
            throw new \Exception('Linha do CSV com número de colunas inválido.');
        }

        return array_combine($header, $row);
    }
}