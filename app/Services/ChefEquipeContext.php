<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChefEquipeContext
{
    public function __construct(
        private CamionsDatabaseResolver $databaseResolver,
    ) {}

    public function connection(): ?string
    {
        return $this->databaseResolver->connection();
    }

    /**
     * Token du chef d'équipe pour l'utilisateur / la requête courante.
     */
    public function resolveToken(?Request $request = null): string
    {
        if ($request) {
            $fromQuery = trim((string) $request->query('token', ''));
            if ($fromQuery !== '') {
                return $fromQuery;
            }

            $fromSession = trim((string) $request->session()->get('chef_equipe_token', ''));
            if ($fromSession !== '') {
                return $fromSession;
            }
        }

        $user = $request?->user() ?? Auth::user();
        if ($user instanceof User) {
            $idChef = (int) ($user->id_chef ?? 0);
            if ($idChef > 0) {
                $chef = $this->databaseResolver->findChefById($idChef);
                if ($chef && $chef['token'] !== '') {
                    return $chef['token'];
                }
            }

            $fromUser = trim((string) ($user->chef_equipe_token ?? ''));
            if ($fromUser !== '') {
                return $fromUser;
            }
        }

        return trim((string) config('services.external_auth.default_chef_equipe_token', ''));
    }

    public function resolveIdChef(?Request $request = null): ?int
    {
        $token = $this->resolveToken($request);
        if ($token !== '') {
            $chef = $this->databaseResolver->findChefByToken($token);
            if ($chef) {
                return $chef['id_chef'];
            }
        }

        $user = $request?->user() ?? Auth::user();
        if ($user instanceof User && (int) ($user->id_chef ?? 0) > 0) {
            return (int) $user->id_chef;
        }

        return null;
    }

    public function resolveChef(?Request $request = null): ?array
    {
        $token = $this->resolveToken($request);
        if ($token !== '') {
            return $this->databaseResolver->findChefByToken($token);
        }

        $idChef = $this->resolveIdChef($request);
        if ($idChef) {
            return $this->databaseResolver->findChefById($idChef);
        }

        return null;
    }

    /**
     * Charge le token en session après connexion (dynamique depuis id_chef).
     */
    public function syncSessionForUser(User $user, Request $request): void
    {
        $token = trim((string) ($user->chef_equipe_token ?? ''));

        if ($token === '' && (int) ($user->id_chef ?? 0) > 0) {
            $chef = $this->databaseResolver->findChefById((int) $user->id_chef);
            $token = (string) ($chef['token'] ?? '');
        }

        if ($token !== '') {
            $request->session()->put('chef_equipe_token', $token);
        } else {
            $request->session()->forget('chef_equipe_token');
        }

        if ((int) ($user->id_chef ?? 0) > 0) {
            $request->session()->put('chef_equipe_id', (int) $user->id_chef);
        } else {
            $request->session()->forget('chef_equipe_id');
        }
    }

    public function findChefById(int $idChef): ?array
    {
        return $this->databaseResolver->findChefById($idChef);
    }

    public function findChefByToken(string $token): ?array
    {
        return $this->databaseResolver->findChefByToken($token);
    }

    /**
     * @return list<array{id_chef: int, nom: string, prenoms: string, nom_complet: string, token: string}>
     */
    public function listChefsEquipe(): array
    {
        return $this->databaseResolver->listChefsEquipe();
    }
}
