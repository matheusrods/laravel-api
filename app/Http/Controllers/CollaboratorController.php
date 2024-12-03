<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collaborator;
use App\Http\Resources\CollaboratorResource;
use App\Jobs\ProcessCollaboratorCsv;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;  // Importa o JWTAuth

class CollaboratorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');  // Usando o middleware JWT para proteger as rotas
    }

    /**
     * @OA\Post(
     *     path="/api/collaborators",
     *     tags={"Collaborators"},
     *     summary="Adicionar um colaborador",
     *     description="Insere um colaborador associado ao gestor logado.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "cpf", "city", "state"},
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", example="joao.silva@email.com"),
     *             @OA\Property(property="cpf", type="string", example="12345678900"),
     *             @OA\Property(property="city", type="string", example="São Paulo"),
     *             @OA\Property(property="state", type="string", example="SP")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Colaborador criado com sucesso"),
     *     @OA\Response(response=400, description="Erro de validação")
     * )
     */
    public function store(Request $request)
    {
        // Validação dos dados recebidos
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'cpf' => 'required',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:2',
        ]);
    
        // Verificar se o colaborador com o mesmo email ou cpf já existe
        $existingCollaborator = Collaborator::where('email', $request->email)
            ->orWhere('cpf', $request->cpf)
            ->first();
    
        // Se já existir, retorna uma mensagem informando que não será criado
        if ($existingCollaborator) {
            return response()->json([
                'message' => 'A collaborator with this email or CPF already exists.',
            ], 422);  // Status 422, indicando que a requisição não foi bem-sucedida devido à duplicidade
        }
    
        // Caso não exista, cria o novo colaborador
        $collaborator = Collaborator::create([
            'name' => $request->name,
            'email' => $request->email,
            'cpf' => $request->cpf,
            'city' => $request->city,
            'state' => $request->state,
            'user_id' => auth()->id(),
        ]);
    
        return response()->json([
            'message' => 'Collaborator created successfully',
            'collaborator' => $collaborator,
        ], 201); // Retorna o status 201, indicando que o colaborador foi criado com sucesso
    }
    

    /**
     * @OA\Get(
     *     path="/api/collaborators",
     *     tags={"Collaborators"},
     *     summary="Listar colaboradores",
     *     description="Retorna todos os colaboradores cadastrados pelo gestor logado.",
     *     @OA\Response(response=200, description="Lista de colaboradores")
     * )
     */
    public function index()
    {
        $collaborators = cache()->remember("collaborators_user_".auth()->id(), 600, function () {
            return Collaborator::where('user_id', auth()->id())->get();
        });

        return CollaboratorResource::collection($collaborators);
    }

    /**
     * Update an existing collaborator.
     *
     * @OA\Put(
     *     path="/api/collaborators/{id}",
     *     tags={"Collaborators"},
     *     summary="Atualizar colaborador",
     *     description="Atualiza as informações de um colaborador existente.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do colaborador",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Novo Nome"),
     *             @OA\Property(property="email", type="string", example="novo.email@example.com"),
     *             @OA\Property(property="cpf", type="string", example="12345678901"),
     *             @OA\Property(property="city", type="string", example="Nova Cidade"),
     *             @OA\Property(property="state", type="string", example="SP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Colaborador atualizado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Collaborator updated successfully"),
     *             @OA\Property(property="collaborator", ref="#/components/schemas/Collaborator")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Colaborador não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acesso não autorizado"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return response()->json(['message' => 'Collaborator not found'], 404);
        }

        // Verificar se o usuário tem permissão
        $this->authorize('update', $collaborator);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email' . $id,
            'cpf' => 'required' . $id,
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:2',
        ]);

        $collaborator->update($request->only(['name', 'email', 'cpf', 'city', 'state']));

        return response()->json([
            'message' => 'Collaborator updated successfully',
            'collaborator' => $collaborator,
        ]);
    }

    /**
     * Delete a collaborator.
     *
     * @OA\Delete(
     *     path="/api/collaborators/{id}",
     *     tags={"Collaborators"},
     *     summary="Deletar colaborador",
     *     description="Exclui um colaborador existente.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do colaborador",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Colaborador deletado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Collaborator deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Colaborador não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acesso não autorizado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return response()->json(['message' => 'Collaborator not found'], 404);
        }

        // Verificar se o usuário tem permissão
        $this->authorize('delete', $collaborator);

        $collaborator->delete();

        return response()->json(['message' => 'Collaborator deleted successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/collaborators/upload",
     *     tags={"Collaborators"},
     *     summary="Upload de CSV para colaboradores",
     *     description="Permite o upload de um arquivo CSV contendo colaboradores para serem processados.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"file"},
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo processado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Processamento iniciado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro no upload do arquivo"
     *     )
     * )
     */
    public function uploadCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Armazena o arquivo para processamento posterior
        $filePath = $request->file('file')->store('uploads');

        // Adiciona a tarefa à fila
        ProcessCollaboratorCsv::dispatch($filePath, Auth::id());

        return response()->json(['message' => 'Processamento iniciado com sucesso']);
    }
}