<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ChefEquipeContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UtilisateurController extends Controller
{
    public function __construct(
        private ChefEquipeContext $chefContext,
    ) {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->check(), 401);
            abort_unless(auth()->user()->role === 'admin', 403);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        return $this->renderListe($request, null, 'utilisateurs.index');
    }

    public function admins(Request $request)
    {
        return $this->renderListe($request, 'admin', 'utilisateurs.admins');
    }

    public function agents(Request $request)
    {
        return $this->renderListe($request, 'agent', 'utilisateurs.agents');
    }

    public function chauffeurs(Request $request)
    {
        return $this->renderListe($request, 'driver', 'utilisateurs.chauffeurs');
    }

    private function renderListe(Request $request, ?string $role, string $view)
    {
        $query = User::query()->orderBy('name');

        if (! is_null($role)) {
            $query->where('role', $role);
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                    ->orWhere('prenom', 'like', "%{$q}%")
                    ->orWhere('login', 'like', "%{$q}%")
                    ->orWhere('contact', 'like', "%{$q}%")
                    ->orWhere('matricule', 'like', "%{$q}%");
            });
        }

        return view($view, [
            'utilisateurs' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('utilisateurs.create', [
            'chefsEquipe' => $this->chefContext->listChefsEquipe(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users,login'],
            'contact' => ['nullable', 'string', 'max:255', 'unique:users,contact'],
            'matricule' => ['nullable', 'string', 'max:255', 'unique:users,matricule'],
            'id_chef' => ['nullable', 'integer', 'min:1'],
            'role' => ['required', 'in:admin,agent,driver'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $user = new User();
        $user->name = $validated['name'];
        $user->prenom = $validated['prenom'] ?? null;
        $user->login = $validated['login'];
        $user->contact = $validated['contact'] ?? null;
        $user->matricule = $validated['matricule'] ?? null;
        $user->id_chef = $this->validateIdChef($validated['id_chef'] ?? null);
        $user->role = $validated['role'];
        $user->password = Hash::make($validated['password']);
        $user->code_pin = Hash::make($pin);

        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        } else {
            $user->avatar = 'user.png';
        }

        $user->save();

        return redirect()->back()->with('code_pin_clair', $pin);
    }

    public function edit(User $utilisateur)
    {
        return view('utilisateurs.edit', [
            'utilisateur' => $utilisateur,
            'chefsEquipe' => $this->chefContext->listChefsEquipe(),
        ]);
    }

    public function update(Request $request, User $utilisateur)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users,login,' . $utilisateur->id],
            'contact' => ['nullable', 'string', 'max:255', 'unique:users,contact,' . $utilisateur->id],
            'matricule' => ['nullable', 'string', 'max:255', 'unique:users,matricule,' . $utilisateur->id],
            'id_chef' => ['nullable', 'integer', 'min:1'],
            'role' => ['required', 'in:admin,agent,driver'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $utilisateur->name = $validated['name'];
        $utilisateur->prenom = $validated['prenom'] ?? null;
        $utilisateur->login = $validated['login'];
        $utilisateur->contact = $validated['contact'] ?? null;
        $utilisateur->matricule = $validated['matricule'] ?? null;
        $utilisateur->id_chef = $this->validateIdChef($validated['id_chef'] ?? null);
        $utilisateur->role = $validated['role'];

        if ($request->hasFile('avatar')) {
            $utilisateur->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        if (! empty($validated['password'])) {
            $utilisateur->password = Hash::make($validated['password']);
        }

        $utilisateur->save();

        if ($utilisateur->id === auth()->id()) {
            $this->chefContext->syncSessionForUser($utilisateur->fresh(), $request);
        }

        return redirect()->back();
    }

    public function destroy(User $utilisateur)
    {
        abort_unless($utilisateur->id !== auth()->id(), 422);

        // Vérifier si l'utilisateur est lié à des pesées
        $hasPesees = \App\Models\Pesee::where('agent_id', $utilisateur->id)->exists();
        
        if ($hasPesees) {
            return redirect()->route('utilisateurs.index')
                ->with('error', 'Impossible de supprimer cet utilisateur car il est lié à des pesées.');
        }

        $utilisateur->delete();

        return redirect()->route('utilisateurs.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    private function validateIdChef(mixed $idChef): ?int
    {
        $idChef = (int) $idChef;
        if ($idChef <= 0) {
            return null;
        }

        return $this->chefContext->findChefById($idChef) ? $idChef : null;
    }
}
