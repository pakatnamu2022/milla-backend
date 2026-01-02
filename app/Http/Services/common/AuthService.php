<?php

namespace App\Http\Services\common;

use App\Http\Resources\gp\gestionsistema\RoleResource;
use App\Http\Resources\gp\gestionsistema\UserResource;
use App\Models\gp\gestionsistema\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
  public function login($request)
  {
    $credentials = $request->only('username', 'password');

    if (Auth::attempt($credentials)) {
      $user = Auth::user();
      $token = $user->createToken('AuthToken', expiresAt: now()->addDays(7));

      $user = User::with('person')->find($user->id);
      $permissionsData = $this->permissions();

      return response()->json([
        'access_token' => $token->plainTextToken,
        'user' => UserResource::make($user),
        'permissions' => $permissionsData['permissions'],
      ]);
    } else {
      return response()->json(['message' => 'Credenciales Inválidas'], 422);
    }
  }

  public function authenticate()
  {
    $user = Auth::user();

    if ($user) {
      $user = User::with('person')->find($user->id);
      $permissionsData = $this->permissions();
      return response()->json([
        'user' => UserResource::make($user),
        'permissions' => $permissionsData['permissions'],
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
      return response()->json(['message' => 'Sesión cerrada correctamente']);
    } else {
      return response()->json(['message' => 'No autenticado'], 401);
    }
  }

  public function permissions()
  {
    $userId = Auth::id();

    // Árbol de Access (permisos básicos CRUD por vista)
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

    // Permisos granulares (permisos específicos por módulo)
    $granularPermissions = $this->getGranularPermissions($userId);

    return [
      'permissions' => [
        'access_tree' => $menuPorEmpresa,
        'permissions_modules' => $granularPermissions,
      ]
    ];
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

  /**
   * Obtener permisos granulares del usuario
   * Retorna un array plano de códigos de permisos
   *
   * @param int $userId
   * @return array
   */
  private function getGranularPermissions(int $userId): array
  {
    return DB::table('permission as p')
      ->join('role_permission as rp', 'p.id', '=', 'rp.permission_id')
      ->join('config_asig_role_user as aru', 'rp.role_id', '=', 'aru.role_id')
      ->where('aru.user_id', $userId)
      ->where('aru.status_deleted', 1)
      ->where('rp.granted', true)
      ->where('p.is_active', true)
      ->distinct()
      ->pluck('p.code')
      ->toArray();
  }

  public function changePassword($request)
  {
    $user = Auth::user();

    if (!$user) {
      return response()->json(['message' => 'No autenticado'], 401);
    }

    if (!Hash::check($request->current_password, $user->password)) {
      return response()->json(['message' => 'La contraseña actual es incorrecta'], 422);
    }

    $user->update([
      'password' => Hash::make($request->new_password),
      'verified_at' => now()
    ]);

    return response()->json(['message' => 'Contraseña actualizada correctamente']);
  }

  public function resetPassword($request)
  {
    $user = User::find($request->user_id);

    if (!$user) {
      return response()->json(['message' => 'Usuario no encontrado'], 404);
    }

    $user->update([
      'password' => Hash::make($user->username),
      'verified_at' => null
    ]);

    return response()->json([
      'message' => 'Contraseña restablecida correctamente',
      'username' => $user->username
    ]);
  }

}
