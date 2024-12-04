<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

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
    public function store(StoreUserRequest $request)
    {
        try {
            
            $user = $this->service->create($request->validated());
    
            Log::info('Usuário criado com sucesso.', ['user_id' => $user->id]);
    
            return response()->json([
                'message' => 'User created successfully',
                'user' => new UserResource($user),
            ], 201);
        
        } catch (\Exception $e) {
            
            Log::error('Erro ao criar usuário.', ['error' => $e->getMessage()]);
    
            if ($e->getMessage() === 'A user with this email already exists.') {
                return response()->json([
                    'message' => 'User already exists',
                ], 409); // HTTP 409: Conflict
            }
    
            return response()->json([
                'message' => 'Error creating user',
                'error' => $e->getMessage(),
            ], 500);
        }
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
        try {
            $users = $this->service->getAll();

            Log::info('Lista de usuários recuperada com sucesso.');

            return UserResource::collection($users);
        } catch (\Exception $e) {
            Log::error('Erro ao listar usuários.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}