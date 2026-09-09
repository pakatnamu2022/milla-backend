<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\StoreSupplyControlRequest;
use App\Http\Requests\tp\comercial\UpdateSupplyControlRequest;
use App\Http\Services\tp\comercial\SupplyControlService;
use App\Models\tp\comercial\SupplyPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupplyControlController extends Controller
{
    protected SupplyControlService $service;

    public function __construct(SupplyControlService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            return response()->json($this->service->list($request));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function getFormData()
    {
        try {
            return response()->json($this->service->getFormData());
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreSupplyControlRequest $request)
    {
        try {
            $data = $request->validated();
            $result = $this->service->store($data);

            return response()->json($result, 201);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function getPhotos(Request $request, $id)
    {
        try {
            $type = $request->input('type');
            $result = $this->service->getPhotosByType((int) $id, $type);

            return response()->json($result);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function uploadTicketPhoto(Request $request, $id)
    {
        try {
            $request->validate([
                'photo' => 'required|string',
                'photo_name' => 'nullable|string|max:255',
            ]);

            $result = $this->service->uploadTicketPhoto(
                (int) $id,
                $request->input('photo')
            );

            return response()->json($result);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            return response()->json($this->service->show($id));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateSupplyControlRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;

            return response()->json($this->service->update($data));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            return response()->json($this->service->destroy($id));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function stats()
    {
        try {
            return response()->json($this->service->getStats());
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function downloadPhoto($id)
    {
        try {
            $photo = SupplyPhoto::where('status_deleted', 1)
                ->with('digitalFile')
                ->findOrFail($id);

            if ($photo->digitalFile && $photo->digitalFile->url) {
                return redirect($photo->digitalFile->url);
            }

            $fullPath = storage_path('app/public/'.$photo->file_path);
            if (file_exists($fullPath)) {
                return response()->download($fullPath, $photo->file_name, [
                    'Content-Type' => $photo->mime_type ?? 'image/jpeg',
                    'Access-Control-Allow-Origin' => '*',
                ]);
            }

            return response()->json(['error' => 'Archivo no encontrado'], 404);

        } catch (Throwable $th) {
            Log::error('Error al descargar foto: '.$th->getMessage());

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
