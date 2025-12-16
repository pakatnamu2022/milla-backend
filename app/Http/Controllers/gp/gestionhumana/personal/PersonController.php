<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionhumana\personal\PersonService;
use App\Models\gp\gestionhumana\personal\Person;
use Exception;
use Illuminate\Http\Request;

class PersonController extends Controller
{
  protected PersonService $service;

  public function __construct(PersonService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Error al listar personas',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function birthdays(Request $request)
  {
    try {
      return $this->service->listBirthdays($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    //
  }

  /**
   * Display the specified resource.
   */
  public function show($id)
  {
    try {
      return $this->service->show($id);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al obtener persona',
        'error' => $e->getMessage()
      ], 404);
    }
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, $id)
  {
    try {
      $data = $request->all();
      $data['id'] = $id;

      return $this->service->update($data);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Error al actualizar persona',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy($id)
  {
    //
  }
}
