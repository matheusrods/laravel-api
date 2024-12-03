<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Criar um novo usuário",
     *     description="Cria um novo usuário com os dados fornecidos.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-12-02T21:33:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-12-02T21:33:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="The email field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validação usando Form Request para melhor estruturação
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // Cache para evitar duplicação desnecessária
        $cacheKey = 'user-' . $validatedData['email'];
        $user = cache()->remember($cacheKey, 600, function () use ($validatedData) {
            return User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);
        });

        // Adicionar job na fila para alguma lógica adicional (exemplo: enviar email de boas-vindas)
        dispatch(function () use ($user) {
            // Exemplo: Lógica adicional ou envio de email
            Log::info("Bem-vindo email enviado para {$user->email}");
        })->onQueue('emails');

        // Retornar resposta JSON usando API Resource
        return response()->json([
            'message' => 'User created successfully',
            'user' => new UserResource($user),
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Listar usuários",
     *     description="Retorna uma lista de usuários cadastrados.",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuários retornada com sucesso",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     */
    public function index()
    {
        // Cache da listagem de usuários por 10 minutos
        $users = cache()->remember('users-list', 600, function () {
            return User::all();
        });

        return UserResource::collection($users);
    }
}

