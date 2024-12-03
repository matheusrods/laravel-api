<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Collaborator;

class CollaboratorPolicy
{
    /**
     * Permite que qualquer gestor visualize seus próprios colaboradores.
     */
    public function viewAny(User $user)
    {
        return true; // Todos os gestores podem listar seus colaboradores
    }

    /**
     * Permite que um gestor edite apenas seus próprios colaboradores.
     */
    public function update(User $user, Collaborator $collaborator)
    {
        return $user->id === $collaborator->user_id;
    }

    /**
     * Permite que um gestor exclua apenas seus próprios colaboradores.
     */
    public function delete(User $user, Collaborator $collaborator)
    {
        return $user->id === $collaborator->user_id;
    }
}
