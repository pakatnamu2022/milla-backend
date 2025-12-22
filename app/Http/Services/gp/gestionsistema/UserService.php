<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\CompanyResource;
use App\Http\Resources\gp\gestionsistema\UserCompleteResource;
use App\Http\Resources\gp\gestionsistema\UserResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Company;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      User::where('status_deleted', 1)->whereHas('person', function ($query) {
        $query->where('status_deleted', 1)->where('status_id', 22);
      }),
      $request,
      User::filters,
      User::sorts,
      UserResource::class,
    );
  }

  private function enrichUserData(array $data): array
  {
    $data['slug'] = !empty($data['descripcion']) ? Str::slug($data['descripcion']) : null;
    return $data;
  }

  public function find($id)
  {
    $view = User::where('id', $id)
      ->where('status_deleted', 1)->first();
    if (!$view) {
      throw new Exception('Usuario no encontrado');
    }
    return $view;
  }

  public function store($data)
  {
    $data = $this->enrichUserData($data);
    $view = User::create($data);
    return new UserResource($view);
  }

  public function show($id)
  {
    return new UserResource($this->find($id));
  }

  public function showComplete($id)
  {
    return new UserCompleteResource($this->find($id));
  }

  public function update($data)
  {
    $view = $this->find($data['id']);
    $data = $this->enrichUserData($data);
    $view->update($data);
    return new UserResource($view);
  }

  public function destroy($id)
  {
    $view = $this->find($id);
    $view->status_deleted = 0;
    $view->save();
    return response()->json(['message' => 'Usuario eliminado correctamente']);
  }

//    GRAFICOS

  public function useStateGraph()
  {
    return User::query()
      ->selectRaw("estado_uso, COUNT(*) as total")
      ->where('status_deleted', 1)
      ->groupBy('estado_uso')
      ->get();
  }

  public function sedeGraph()
  {
    return User::selectRaw('companies.abbreviation as sede, COUNT(*) as total')
      ->join('config_sede', 'config_sede.id', '=', 'help_vistas.sede_id')
      ->join('companies', 'companies.id', '=', 'config_sede.empresa_id')
      ->where('help_vistas.status_deleted', 1)
      ->groupBy('companies.abbreviation')
      ->orderByDesc('total')
      ->get();
  }

  public function getMyCompanies()
  {
    $user = auth()->user();

    // Verificar que el usuario tenga una persona relacionada con VAT
    if (!$user->person || !$user->person->vat) {
      return CompanyResource::collection(collect());
    }

    $vat = $user->person->vat;

    // Buscar todos los workers con el mismo VAT y status_id = 22 (ACTIVO)
    $workers = Worker::where('vat', $vat)
      ->where('status_id', 22)
      ->where('status_deleted', 1)
      ->with('sede')
      ->get();

    // Obtener empresa_id únicos desde las sedes de los workers
    $companyIds = $workers
      ->pluck('sede.empresa_id')
      ->unique()
      ->filter();

    // Obtener empresas únicas
    $companies = Company::whereIn('id', $companyIds)
      ->get();

    return CompanyResource::collection($companies);
  }
}
