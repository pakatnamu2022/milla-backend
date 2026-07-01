<?php

namespace App\Http\Services\common;

use App\Http\Resources\gp\gestionsistema\ActiveSessionResource;
use App\Http\Resources\gp\gestionsistema\RoleResource;
use App\Http\Resources\gp\gestionsistema\UserResource;
use App\Models\GeneralMaster;
use App\Models\gp\gestionsistema\Role;
use App\Models\PasswordResetToken;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AuthService
{
  public function login($request)
  {
    $credentials = $request->only('username', 'password');

    if (Auth::attempt($credentials)) {
      $user = Auth::user();
      $user = User::with('person')->find($user->id);
      $person = $user->person;

      if (!$person) {
        $user->tokens()->delete();
        throw new Exception('Credenciales Inválidas', 401);
      }

      if ($user->two_factor_enabled) {
        Auth::logout();
        $pendingToken = Str::random(40);
        Cache::put("2fa_pending:{$pendingToken}", $user->id, now()->addMinutes(5));

        return response()->json([
          'requires_2fa'  => true,
          'pending_token' => $pendingToken,
        ]);
      }

      $token = $user->createToken('AuthToken', expiresAt: now()->addDays(7));
      $permissionsData = $this->permissions($user->id);

      return response()->json([
        'access_token' => $token->plainTextToken,
        'user'         => UserResource::make($user),
        'permissions'  => $permissionsData['permissions'],
        'general'      => $this->getGeneralSettings(),
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
      $person = $user->person;
      if (!$person) {
        $user->tokens()->delete();
        throw new Exception('Credenciales Inválidas', 401);
      }

      return response()->json([
        'user'        => UserResource::make($user),
        'permissions' => $permissionsData['permissions'],
        'general'     => $this->getGeneralSettings(),
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

  public function permissions(?int $userId = null)
  {
    $userId = $userId ?? Auth::id();

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
          'empresa_id'          => $empresaId,
          'empresa_abreviatura' => $grupo[0]->abbreviation ?? 'NA',
          'empresa_nombre'      => $grupo[0]->empresa_nombre,
          'menu'                => $menu,
        ];
      }
    }

    // Permisos granulares (permisos específicos por módulo)
    $granularPermissions = $this->getGranularPermissions($userId);

    return [
      'permissions' => [
        'access_tree'         => $menuPorEmpresa,
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
        'view'   => false,
        'create' => false,
        'edit'   => false,
        'delete' => false,
      ];
    }

    // Aquí transformas a tu estructura esperada:
    $modules = $this->transformarAFormatoFront($views);

    return response()->json([
      'role'    => RoleResource::make(Role::find($roleId)),
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
        'view'   => (bool)$item->view,
        'create' => (bool)$item->create,
        'edit'   => (bool)$item->edit,
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

    if (Hash::check($request->new_password, $user->password)) {
      return response()->json(['message' => 'La nueva contraseña no puede ser igual a la contraseña actual'], 422);
    }

    $user->update([
      'password'    => Hash::make($request->new_password),
      'verified_at' => now()
    ]);

    return response()->json(['message' => 'Contraseña actualizada correctamente. Todas las sesiones han sido cerradas.']);
  }

  public function resetPassword($request)
  {
    $user = User::find($request->user_id);

    if (!$user) {
      return response()->json(['message' => 'Usuario no encontrado'], 404);
    }

    $user->update([
      'password'    => Hash::make($user->username),
      'verified_at' => null
    ]);

    // Invalidar todos los tokens de sesión del usuario
    $user->tokens()->delete();

    return response()->json([
      'message'  => 'Contraseña restablecida correctamente',
      'username' => $user->username
    ]);
  }

  public function resetPasswordByCompany($request)
  {
    // Obtener todos los usuarios de la empresa a través de la relación person -> sede -> empresa
    $users = User::whereHas('person.sede', function ($query) use ($request) {
      $query->where('empresa_id', $request->company_id);
    })->get();

    if ($users->isEmpty()) {
      return response()->json(['message' => 'No se encontraron usuarios para esta empresa'], 404);
    }

    $updatedCount = 0;
    foreach ($users as $user) {
      $user->update([
        'password'    => Hash::make($user->username),
        'verified_at' => null
      ]);
      $updatedCount++;
    }

    return response()->json([
      'message'       => 'Contraseñas restablecidas correctamente',
      'users_updated' => $updatedCount,
      'company_id'    => $request->company_id
    ]);
  }

  public function forgotPassword($request)
  {
    $user = User::with('person')->where('username', $request->username)->first();

    if (!$user || !$user->person) {
      return response()->json(['message' => 'Usuario no encontrado'], 404);
    }

    $email2 = $user->person->email2;

    if (!$email2) {
      return response()->json(['message' => 'Este usuario no tiene correo corporativo registrado. Contacta al administrador.'], 422);
    }

    PasswordResetToken::where('email', $email2)->delete();

    $plainToken = Str::random(64);

    PasswordResetToken::create([
      'email'      => $email2,
      'token'      => Hash::make($plainToken),
      'created_at' => now(),
    ]);

    $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $plainToken . '&email=' . urlencode($email2);

    $emailService = app(EmailService::class);
    $emailService->send([
      'to'       => [$email2],
      'subject'  => 'Restablecer contraseña — Sian',
      'template' => 'emails.password-reset',
      'data'     => [
        'name'     => $user->person->nombre_completo ?? $user->name,
        'resetUrl' => $resetUrl,
      ],
    ]);

    return response()->json([
      'message' => "Se envió un enlace de restablecimiento a tu correo corporativo {$email2}."
    ]);
  }

  public function resetPasswordByToken($request)
  {
    $record = PasswordResetToken::where('email', $request->email)->first();

    if (!$record) {
      return response()->json(['message' => 'Token inválido o expirado'], 422);
    }

    if ($record->isExpired()) {
      $record->delete();
      return response()->json(['message' => 'El enlace ha expirado. Solicita uno nuevo.'], 422);
    }

    if (!Hash::check($request->token, $record->token)) {
      return response()->json(['message' => 'Token inválido o expirado'], 422);
    }

    // Buscar usuario por email2 de su persona
    $user = User::whereHas('person', function ($q) use ($request) {
      $q->where('email2', $request->email);
    })->first();

    if (!$user) {
      return response()->json(['message' => 'Usuario no encontrado'], 404);
    }

    $user->update([
      'password'    => Hash::make($request->password),
      'verified_at' => now(),
    ]);

    $user->tokens()->delete();
    $record->delete();

    return response()->json(['message' => 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.']);
  }

  public function activeSessions()
  {
    $todayStart = now()->startOfDay();

    $tokenData = DB::table('personal_access_tokens')
      ->where('tokenable_type', 'App\\Models\\User')
      ->where(function ($q) use ($todayStart) {
        $q->where('created_at', '>=', $todayStart)
          ->orWhere('last_used_at', '>=', $todayStart);
      })
      ->where(function ($q) {
        $q->whereNull('expires_at')
          ->orWhere('expires_at', '>', now());
      })
      ->select(
        'tokenable_id as user_id',
        DB::raw('MIN(created_at) as login_at'),
        DB::raw('MAX(last_used_at) as last_seen_at'),
        DB::raw('COUNT(id) as session_count'),
      )
      ->groupBy('tokenable_id')
      ->get()
      ->keyBy('user_id');

    $users = User::with(['person.position', 'person.sede.company'])
      ->whereIn('id', $tokenData->keys())
      ->get()
      ->map(function ($user) use ($tokenData) {
        $tokens = $tokenData->get($user->id);
        $user->login_at      = $tokens->login_at;
        $user->last_seen_at  = $tokens->last_seen_at;
        $user->session_count = $tokens->session_count;
        return $user;
      })
      ->sortByDesc('last_seen_at')
      ->values();

    $onlineThreshold = now()->subMinutes(15);
    $onlineCount = $users->filter(
      fn($u) => $u->last_seen_at && Carbon::parse($u->last_seen_at)->gte($onlineThreshold)
    )->count();

    return response()->json([
      'total'  => $users->count(),
      'online' => $onlineCount,
      'users'  => ActiveSessionResource::collection($users),
    ]);
  }

  /**
   * Obtener configuraciones generales del sistema
   *
   * @return array
   */
  private function getGeneralSettings(): array
  {
    $freightCommission = GeneralMaster::find(GeneralMaster::FREIGHT_COMMISSION_ID);

    return [
      'freight_commission' => (float)($freightCommission->value ?? 0.05),
    ];
  }

}
