<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class AccesPlateforme
{
    private const STORAGE_FILE = 'acces_roles.json';

    public const SESSION_PROFIL_KEY = 'auth_profil';

    /** @var list<string>|null */
    private ?array $permissionsCache = null;

    private ?string $profilCache = null;

    public function __construct(
        private ParametresAccess $parametresAccess,
        private ChefEquipeSession $chefSession,
    ) {}

    public function peutGererAcces(?Request $request = null): bool
    {
        return $this->peut('acces.gerer', $request);
    }

    public function estChefPgf(?array $chef): bool
    {
        if ($chef === null) {
            return false;
        }

        $loginAttendu = mb_strtolower(trim((string) config('services.superadmin.chef_login', 'pgf')));
        $tokenAttendu = trim((string) config('services.superadmin.chef_token', ''));

        $loginChef = mb_strtolower(trim((string) ($chef['login'] ?? '')));
        if ($loginAttendu !== '' && $loginChef === $loginAttendu) {
            return true;
        }

        if ($tokenAttendu !== '' && hash_equals($tokenAttendu, (string) ($chef['token'] ?? ''))) {
            return true;
        }

        $nom = mb_strtoupper(trim((string) ($chef['nom_complet'] ?? '')));

        return str_contains($nom, 'PGF AFRICA') || $nom === 'PGF';
    }

    public function profilCourant(?Request $request = null): string
    {
        if ($this->profilCache !== null) {
            return $this->profilCache;
        }

        $request ??= request();

        if ($this->parametresAccess->allowed($request)) {
            return $this->profilCache = 'superadmin';
        }

        // Prioritaire : profil posé à la connexion (évite de confondre gestionnaire + session chef PGF).
        if ($request->hasSession()) {
            $profilSession = mb_strtolower(trim((string) $request->session()->get(self::SESSION_PROFIL_KEY, '')));
            if ($profilSession !== '' && array_key_exists($profilSession, config('acces_plateforme.profils', []))) {
                return $this->profilCache = $profilSession;
            }
        }

        $user = Auth::user();
        if ($user instanceof User) {
            $role = mb_strtolower(trim((string) ($user->role ?? '')));
            $map = [
                'gestionnaire' => 'gestionnaire',
                'admin' => 'admin',
                'agent' => 'agent',
                'driver' => 'driver',
                'commis' => 'commis',
            ];
            if (isset($map[$role])) {
                return $this->profilCache = $map[$role];
            }
        }

        $chef = $this->chefSession->chef($request);
        if ($this->estChefPgf($chef)) {
            return $this->profilCache = 'pgf';
        }

        if ($chef !== null) {
            return $this->profilCache = 'chef_equipe';
        }

        return $this->profilCache = 'chef_equipe';
    }

    public function memoriserProfil(Request $request, string $profil): void
    {
        $request->session()->put(self::SESSION_PROFIL_KEY, $profil);
        $this->profilCache = $profil;
        $this->permissionsCache = null;
    }

    public function oublierProfil(Request $request): void
    {
        $request->session()->forget(self::SESSION_PROFIL_KEY);
        $this->profilCache = null;
        $this->permissionsCache = null;
    }

    /**
     * True si le profil courant a au moins une des permissions listées.
     */
    public function peut(string ...$permissions): bool
    {
        if ($permissions === []) {
            return false;
        }

        $owned = $this->permissionsCourantes();
        foreach ($permissions as $key) {
            if (in_array($key, $owned, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function permissionsCourantes(?Request $request = null): array
    {
        if ($this->permissionsCache !== null) {
            return $this->permissionsCache;
        }

        return $this->permissionsCache = $this->permissionsPourProfil($this->profilCourant($request));
    }

    /**
     * @return array{name: string, role_label: string, avatar_url: string|null}
     */
    public function identiteAffichee(?Request $request = null): array
    {
        $request ??= request();
        $profil = $this->profilCourant($request);
        $labels = [
            'superadmin' => 'Super administrateur',
            'pgf' => 'Chef PGF',
            'gestionnaire' => 'Gestionnaire',
            'chef_equipe' => 'Chef d\'équipe',
            'admin' => 'Administrateur',
            'commis' => 'Commis de pont',
            'agent' => 'Agent',
            'driver' => 'Chauffeur',
        ];

        $user = Auth::user();
        if ($user instanceof User) {
            $name = trim((string) $user->name . ' ' . (string) ($user->prenom ?? ''));
            if ($name === '') {
                $name = (string) ($user->login ?? 'Utilisateur');
            }

            return [
                'name' => $name,
                'role_label' => $labels[$profil] ?? ucfirst($profil),
                'avatar_url' => $user->avatar_url ?? null,
            ];
        }

        if ($profil === 'superadmin') {
            return [
                'name' => 'Super administrateur',
                'role_label' => 'Super administrateur',
                'avatar_url' => null,
            ];
        }

        $chef = $this->chefSession->chef($request);

        return [
            'name' => (string) ($chef['nom_complet'] ?? 'Chef d\'équipe'),
            'role_label' => $labels[$profil] ?? 'Chef d\'équipe',
            'avatar_url' => null,
        ];
    }

    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public function groupes(): array
    {
        return config('acces_plateforme.groupes', []);
    }

    /**
     * @return list<string>
     */
    public function toutesLesPermissions(): array
    {
        $keys = [];
        foreach ($this->groupes() as $groupe) {
            foreach (array_keys($groupe['permissions'] ?? []) as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function totalPermissions(): int
    {
        return count($this->toutesLesPermissions());
    }

    /**
     * @return array<string, list<string>>
     */
    public function permissionsEnregistrees(): array
    {
        $path = $this->storagePath();
        if (! File::exists($path)) {
            return [];
        }

        $data = json_decode((string) File::get($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @return list<string>
     */
    public function permissionsPourProfil(string $profilKey): array
    {
        $saved = $this->permissionsEnregistrees();
        if (array_key_exists($profilKey, $saved) && is_array($saved[$profilKey])) {
            return array_values(array_unique(array_map('strval', $saved[$profilKey])));
        }

        $defaults = config("acces_plateforme.profils.{$profilKey}.permissions", []);
        if ($defaults === '*') {
            return $this->toutesLesPermissions();
        }

        return is_array($defaults) ? array_values($defaults) : [];
    }

    /**
     * @param  list<string>  $permissions
     */
    public function enregistrerPermissions(string $profilKey, array $permissions): void
    {
        $valides = array_values(array_intersect(
            array_unique(array_map('strval', $permissions)),
            $this->toutesLesPermissions()
        ));

        $saved = $this->permissionsEnregistrees();
        $saved[$profilKey] = $valides;

        File::ensureDirectoryExists(dirname($this->storagePath()));
        File::put(
            $this->storagePath(),
            json_encode($saved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->permissionsCache = null;
        $this->profilCache = null;
    }

    public function profilExiste(string $profilKey): bool
    {
        return array_key_exists($profilKey, config('acces_plateforme.profils', []));
    }

    /**
     * @return list<array{
     *   key: string,
     *   label: string,
     *   description: string,
     *   couleur: string,
     *   permissions: list<string>,
     *   count: int,
     *   total: int,
     *   modules_resume: string
     * }>
     */
    public function rolesPourListe(): array
    {
        $profils = config('acces_plateforme.profils', []);
        $total = $this->totalPermissions();
        $groupes = $this->groupes();
        $result = [];

        foreach ($profils as $key => $profil) {
            $perms = $this->permissionsPourProfil($key);
            $result[] = [
                'key' => $key,
                'label' => (string) ($profil['label'] ?? $key),
                'description' => (string) ($profil['description'] ?? ''),
                'couleur' => (string) ($profil['couleur'] ?? 'secondary'),
                'permissions' => $perms,
                'count' => count($perms),
                'total' => $total,
                'modules_resume' => $this->resumeModules($perms, $groupes),
            ];
        }

        return $result;
    }

    /**
     * @return array{
     *   key: string,
     *   label: string,
     *   description: string,
     *   couleur: string,
     *   permissions: list<string>,
     *   groupes: list<array{key: string, label: string, permissions: list<array{key: string, label: string, checked: bool}>}>
     * }|null
     */
    public function rolePourEdition(string $profilKey): ?array
    {
        $profil = config("acces_plateforme.profils.{$profilKey}");
        if (! is_array($profil)) {
            return null;
        }

        $selected = $this->permissionsPourProfil($profilKey);
        $groupes = [];

        foreach ($this->groupes() as $groupeKey => $groupe) {
            $items = [];
            foreach (($groupe['permissions'] ?? []) as $permKey => $permLabel) {
                $items[] = [
                    'key' => $permKey,
                    'label' => $permLabel,
                    'checked' => in_array($permKey, $selected, true),
                ];
            }
            $groupes[] = [
                'key' => $groupeKey,
                'label' => (string) ($groupe['label'] ?? $groupeKey),
                'permissions' => $items,
            ];
        }

        return [
            'key' => $profilKey,
            'label' => (string) ($profil['label'] ?? $profilKey),
            'description' => (string) ($profil['description'] ?? ''),
            'couleur' => (string) ($profil['couleur'] ?? 'secondary'),
            'permissions' => $selected,
            'groupes' => $groupes,
        ];
    }

    private function storagePath(): string
    {
        return storage_path('app/' . self::STORAGE_FILE);
    }

    /**
     * @param  list<string>  $permissions
     * @param  array<string, array{label: string, permissions: array<string, string>}>  $groupes
     */
    private function resumeModules(array $permissions, array $groupes): string
    {
        $labels = [];
        foreach ($groupes as $groupe) {
            $keys = array_keys($groupe['permissions'] ?? []);
            if ($keys === []) {
                continue;
            }
            if (count(array_intersect($keys, $permissions)) > 0) {
                $labels[] = (string) ($groupe['label'] ?? '');
            }
        }

        $labels = array_values(array_filter($labels));
        if ($labels === []) {
            return 'Aucun module';
        }

        $preview = array_slice($labels, 0, 4);
        $text = implode(', ', $preview);
        if (count($labels) > 4) {
            $text .= '…';
        }

        return $text;
    }
}
