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
 */
class ApiDocumentation
{
    // Este arquivo centraliza as anotações globais.
}
