<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\TelephoneAccountResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\TelephoneAccount;
use Exception;
use Illuminate\Http\Request;

class TelephoneAccountService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            TelephoneAccount::query(),
            $request,
            TelephoneAccount::filters,
            TelephoneAccount::sorts,
            TelephoneAccountResource::class,
        );
    }

    public function store($data)
    {
        $telephoneAccount = TelephoneAccount::create($data);
        return new TelephoneAccountResource(TelephoneAccount::find($telephoneAccount->id));
    }

    public function find($id)
    {
        $telephoneAccount = TelephoneAccount::where('id', $id)->first();
        if (!$telephoneAccount) {
            throw new Exception('Cuenta telefónica no encontrada');
        }
        return $telephoneAccount;
    }

    public function show($id)
    {
        return new TelephoneAccountResource($this->find($id));
    }

    public function update($data)
    {
        $telephoneAccount = $this->find($data['id']);
        $telephoneAccount->update($data);
        return new TelephoneAccountResource($telephoneAccount);
    }

    public function destroy($id)
    {
        $telephoneAccount = $this->find($id);
        $telephoneAccount->delete();
        return response()->json(['message' => 'Cuenta telefónica eliminada correctamente']);
    }
}