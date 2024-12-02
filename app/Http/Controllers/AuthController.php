<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
     /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Realiza o login de um usuário",
     *     description="Autentica o usuário e retorna um token JWT.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="gestor@empresa.com"),
     *             @OA\Property(property="password", type="string", example="senha123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token JWT gerado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        // Validação dos dados de entrada
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        // Use explicitamente o guard 'api'
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60 // Expiração do token
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout do usuário",
     *     description="Invalidar o token JWT do usuário logado.",
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        // Invalidar o token do usuário logado
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @OA\Get(
     *     path="/api/test",
     *     tags={"Test"},
     *     summary="Endpoint de teste",
     *     description="Verifica se o Swagger está configurado corretamente.",
     *     @OA\Response(
     *         response=200,
     *         description="Sucesso"
     *     )
     * )
     */
    public function test()
    {
        return response()->json(['message' => 'Test API endpoint working']);
    }
    }
