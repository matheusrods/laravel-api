<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            return route('login'); // Esse redirecionamento é usado para aplicações web
        }

        // Para APIs, retorna nulo e deixe o middleware gerar uma resposta 401
        return null;
    }

    /**
     * Handle unauthenticated requests for APIs.
     */
    protected function unauthenticated($request, array $guards)
    {
        abort(response()->json(['error' => 'Unauthorized'], 401));
    }
}
