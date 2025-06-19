<?php

namespace App\Http\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function login($request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('AuthToken', expiresAt: now()->addDays(7));

            $user = User::with('person')->find($user->id);

            return response()->json([
                'access_token' => $token->plainTextToken,
                'user' => UserResource::make($user),
            ]);
        } else {
            return response()->json(['message' => 'Credenciales Inválidades'], 422);
        }
    }

    public function authenticate()
    {
        $user = Auth::user();

        if ($user) {
            $user = User::with('person')->find($user->id);
            return response()->json([
                'user' => UserResource::make($user),
            ]);
        } else {
            return response()->json(['message' => 'No autenticado'], 401);
        }
    }

    public function logout()
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
            return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
        } else {
            return response()->json(['message' => 'No autenticado'], 401);
        }
    }

    public function permissions()
    {
        $userId = Auth::id();

        $vistas = $this->getAllVistas();
        $permitidas = $this->getVistasPermitidas($userId);
        $vistasFiltradas = $this->filtrarVistas($vistas, $permitidas);
        $menu = $this->construirArbol($vistasFiltradas);

        return ['opcionesMenu' => $menu];
    }

    private function getAllVistas()
    {
        return DB::select("SELECT * FROM config_vista WHERE status_deleted = 1");
    }

    private function getVistasPermitidas($userId)
    {
        return DB::table('config_asigxvistaxrole as axvr')
            ->join('config_asig_role_user as aru', 'axvr.role_id', '=', 'aru.role_id')
            ->where('axvr.status_deleted', 1)
            ->where('aru.status_deleted', 1)
            ->where('aru.user_id', $userId)
            ->where('axvr.ver', 1)
            ->distinct()
            ->pluck('axvr.vista_id')
            ->toArray();
    }

    private function filtrarVistas($vistas, $permitidas)
    {
        return array_filter($vistas, function ($vista) use ($permitidas) {
            $esRaiz = is_null($vista->idPadre) && is_null($vista->idSubPadre) && is_null($vista->idHijo);
            $sinRuta = empty($vista->ruta);
            return $esRaiz || $sinRuta || in_array($vista->id, $permitidas);
        });
    }

    private function construirArbol($vistas)
    {
        $padres = [];

        foreach ($vistas as $vista) {
            if (is_null($vista->idPadre) && is_null($vista->idSubPadre) && is_null($vista->idHijo)) {
                $padres[] = $vista;
            }
        }

        foreach ($padres as &$padre) {
            $padre->subpadres = array_values(array_filter(array_map(function ($subpadre) use ($padre, $vistas) {
                if ($subpadre->idPadre !== $padre->id) return null;

                $subpadre->hijos = array_values(array_filter(array_map(function ($hijo) use ($subpadre, $vistas) {
                    if ($hijo->idSubPadre !== $subpadre->id) return null;

                    $hijo->hijos = array_values(array_filter($vistas, fn($subhijo) => $subhijo->idHijo === $hijo->id && !empty($subhijo->ruta)));
                    return (!empty($hijo->ruta) || count($hijo->hijos)) ? $hijo : null;
                }, $vistas)));

                return (!empty($subpadre->ruta) || count($subpadre->hijos)) ? $subpadre : null;
            }, $vistas)));
        }

        return array_values(array_filter($padres, fn($padre) => !empty($padre->ruta) || count($padre->subpadres ?? [])));
    }

}
