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
            $permissions = $this->permissions();

            return response()->json([
                'access_token' => $token->plainTextToken,
                'user' => UserResource::make($user),
                'permissions' => $permissions['opcionesMenu'],
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
            $permissions = $this->permissions();
            return response()->json([
                'user' => UserResource::make($user),
                'permissions' => $permissions['opcionesMenu'],
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

        $vistas = $this->getAllVistasConEmpresa();
        $permitidas = $this->getVistasPermitidas($userId);

        $vistasFiltradas = $this->filtrarVistas($vistas, $permitidas);

        $agrupadasPorEmpresa = $this->agruparPorEmpresa($vistasFiltradas);

        $menuPorEmpresa = [];

        foreach ($agrupadasPorEmpresa as $empresaId => $grupo) {
            $menu = $this->construirArbolUnico($grupo);

            if (count($menu)) {
                $menuPorEmpresa[] = [
                    'empresa_id' => $empresaId,
                    'empresa_nombre' => $grupo[0]->empresa_nombre,
                    'menu' => $menu,
                ];
            }
        }


        return ['opcionesMenu' => $menuPorEmpresa];
    }

    private function getAllVistasConEmpresa()
    {
        return DB::table('config_vista as cv')
            ->leftJoin('companies as c', 'cv.company_id', '=', 'c.id')
            ->where('cv.status_deleted', 1)
            ->whereNotNull('cv.company_id')
            ->select(
                'cv.*',
                'c.id as empresa_id',
                'c.name as empresa_nombre'
            )
            ->get();
    }

    private function agruparPorEmpresa($vistas)
    {
        $agrupadas = [];

        foreach ($vistas as $vista) {
            $empresaId = $vista->empresa_id ?? 0; // puedes usar 0 para "sin empresa"
            $agrupadas[$empresaId][] = $vista;
        }

        return $agrupadas;
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
        $idsPermitidos = collect($vistas)
            ->filter(fn($vista) => in_array($vista->id, $permitidas))
            ->pluck('id')
            ->toArray();

        return $vistas->filter(function ($vista) use ($idsPermitidos) {
            return in_array($vista->id, $idsPermitidos) || $vista->parent_id === null;
        })->values(); // <-- devuelve una colección limpia
    }


    private function construirArbolUnico($vistas)
    {
        $agrupadas = [];

        foreach ($vistas as $vista) {
            $agrupadas[$vista->parent_id ?? 0][] = $vista;
        }

        $construir = function ($parentId) use (&$construir, $agrupadas) {
            $items = $agrupadas[$parentId] ?? [];

            $resultado = [];

            foreach ($items as $item) {
                $item->children = $construir($item->id);

                // incluir solo si tiene ruta o hijos visibles
                if (!empty($item->ruta) || count($item->children)) {
                    $resultado[] = $item;
                }
            }

            return $resultado;
        };

        return $construir(0);
    }


}
