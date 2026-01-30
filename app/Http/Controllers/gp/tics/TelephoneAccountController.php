<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\IndexTelephoneAccountRequest;
use App\Http\Requests\gp\tics\StoreTelephoneAccountRequest;
use App\Http\Requests\gp\tics\UpdateTelephoneAccountRequest;
use App\Http\Services\gp\tics\TelephoneAccountService;

class TelephoneAccountController extends Controller
{
    protected TelephoneAccountService $service;

    public function __construct(TelephoneAccountService $service)
    {
        $this->service = $service;
    }

    public function index(IndexTelephoneAccountRequest $request)
    {
        return $this->service->list($request);
    }

    public function store(StoreTelephoneAccountRequest $request)
    {
        $data = $request->validated();
        return response()->json($this->service->store($data));
    }

    public function show($id)
    {
        try {
            return response()->json($this->service->show($id));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(UpdateTelephoneAccountRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            return response()->json($this->service->update($data));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy($id)
    {
        try {
            return $this->service->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
