<?php

namespace App\Http\Services\common;

use App\Http\Resources\gp\gestionsistema\RoleResource;
use App\Http\Resources\gp\gestionsistema\UserResource;
use App\Models\gp\gestionsistema\Role;
use App\Models\User;
use Illuminate\Http\Request;
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
        'permissions' => $permissions['permissions'],
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
        'permissions' => $permissions['permissions'],
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
          'empresa_abreviatura' => $grupo[0]->abbreviation ?? 'NA',
          'empresa_nombre' => $grupo[0]->empresa_nombre,
          'menu' => $menu,
        ];
      }
    }

    return ['permissions' => $menuPorEmpresa];
  }

  public function modules(Request $request)
  {
    $roleId = $request->query('roleId');

    $views = $this->getAllVistasConEmpresa(); // como ya lo tienes
    $permissions = $roleId
      ? $this->getVistasConPermisosPorRol($roleId)
      : [];


    foreach ($views as $vista) {
      $vista->permissions = $permissions[$vista->id] ?? [
        'view' => false,
        'create' => false,
        'edit' => false,
        'delete' => false,
      ];
    }

    // Aquí transformas a tu estructura esperada:
    $modules = $this->transformarAFormatoFront($views);

    return response()->json([
      'role' => RoleResource::make(Role::find($roleId)),
      'modules' => $modules,
    ]);
  }

  private function getVistasConPermisosPorRol(int $roleId): array
  {
    return DB::table('config_asigxvistaxrole as axvr')
      ->where('axvr.status_deleted', 1)
      ->where('axvr.role_id', $roleId)
      ->select(
        'axvr.vista_id',
        'axvr.ver as view',
        'axvr.crear as create',
        'axvr.editar as edit',
        'axvr.anular as delete'
      )
      ->get()
      ->keyBy('vista_id')
      ->map(fn($item) => [
        'view' => (bool)$item->view,
        'create' => (bool)$item->create,
        'edit' => (bool)$item->edit,
        'delete' => (bool)$item->delete,
      ])
      ->toArray();
  }


  private function transformarAFormatoFront($vistas)
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
      }

      // Reordenamos: primero los que tienen hijos
      usort($items, function ($a, $b) {
        $aHasChildren = count($a->children ?? []) > 0;
        $bHasChildren = count($b->children ?? []) > 0;

        return $aHasChildren === $bHasChildren ? 0 : ($aHasChildren ? -1 : 1);
      });

      foreach ($items as $item) {
//                CAMBIAR LUEGO POR LA RUTA REAL QUE ES ROUTE
        if (!empty($item->ruta) || count($item->children)) {
          $resultado[] = $item;
        }
      }

      return $resultado;
    };

    return $construir(0);
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
        'c.name as empresa_nombre',
        DB::raw('LOWER(c.abbreviation) as abbreviation'),
      )
      ->orderBy('cv.parent_id')
      ->orderBy('cv.descripcion')
      ->get()->map(function ($item) {
        $item->submodule = (bool)$item->submodule;
        return $item;
      });
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

  private function getAllVistas()
  {
    return DB::table('config_vista')
      ->where('status_deleted', 1)
      ->select('id')
      ->pluck('id')
      ->toArray();
  }

  private function filtrarVistas($vistas, $permitidas)
  {
    $permitidasSet = collect($permitidas)->flip(); // para acceso rápido
    $vistasPorId = collect($vistas)->keyBy('id');

    $idsFinales = collect();

    foreach ($vistas as $vista) {
      if (isset($permitidasSet[$vista->id])) {
        $actual = $vista;
        while ($actual) {
          $idsFinales->push($actual->id);
          $actual = $vistasPorId->get($actual->parent_id);
        }
      }
    }

    $idsFinales = $idsFinales->unique()->values();

    return collect($vistas)->filter(fn($vista) => $idsFinales->contains($vista->id))->values();
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
      }

      // Reordenamos: primero los que tienen hijos
      usort($items, function ($a, $b) {
        $aHasChildren = count($a->children ?? []) > 0;
        $bHasChildren = count($b->children ?? []) > 0;

        return $aHasChildren === $bHasChildren ? 0 : ($aHasChildren ? -1 : 1);
      });

      foreach ($items as $item) {
//                CAMBIAR LUEGO POR LA RUTA REAL QUE ES ROUTE
        if (!empty($item->route) || count($item->children)) {
          $resultado[] = $item;
        }
      }

      return $resultado;
    };

    return $construir(0);
  }


}
