<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Minha API",
 *         description="Documentação da API",
 *         @OA\Contact(
 *             email="matheusdevtic@gmail.com"
 *         )
 *     ),
 *     @OA\Server(
 *         url="http://localhost:8000",
 *         description="Servidor Local"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Schema do usuário",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1,
 *         description="ID do usuário"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="John Doe",
 *         description="Nome do usuário"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         example="john.doe@example.com",
 *         description="Email do usuário"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-12-02T20:30:00Z",
 *         description="Data de criação"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-12-02T20:30:00Z",
 *         description="Data da última atualização"
 *     )
 * )
 * 
 *  * @OA\Schema(
 *     schema="Collaborator",
 *     type="object",
 *     title="Collaborator",
 *     description="Modelo de colaborador",
 *     required={"name", "email", "cpf", "city", "state"},
 *     @OA\Property(property="id", type="integer", description="ID do colaborador"),
 *     @OA\Property(property="name", type="string", description="Nome do colaborador"),
 *     @OA\Property(property="email", type="string", format="email", description="Email do colaborador"),
 *     @OA\Property(property="cpf", type="string", description="CPF do colaborador"),
 *     @OA\Property(property="city", type="string", description="Cidade do colaborador"),
 *     @OA\Property(property="state", type="string", description="Estado do colaborador"),
 *     @OA\Property(property="user_id", type="integer", description="ID do usuário (gestor) associado"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Data da última atualização")
 * )
 */
class ApiDocumentation
{
}
