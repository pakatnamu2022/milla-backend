<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\tics\PhoneLineService;
use App\Models\gp\tics\PhoneLine;
use Illuminate\Http\Request;
use Throwable;

class PhoneLineController extends Controller
{
    protected PhoneLineService $service;

    public function __construct(PhoneLineService $service)
    {
        $this->service = $service;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(PhoneLine $phoneLine)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PhoneLine $phoneLine)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PhoneLine $phoneLine)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PhoneLine $phoneLine)
    {
        //
    }

    /**
     * Importar líneas telefónicas desde un archivo Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return $this->error('Archivo no encontrado o no válido. Asegúrate de enviar un archivo con el campo "file".');
        }

        try {
            $result = $this->service->importFromExcel($request->file('file'));

            if ($result['success']) {
                return $this->success($result, $result['message']);
            } else {
                return $this->error($result['message'], $result);
            }
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
