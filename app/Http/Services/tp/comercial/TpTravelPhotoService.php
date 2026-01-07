<?php

namespace App\Http\Services\tp\comercial;

use App\Helpers\DeviceHelper;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Resources\tp\comercial\TpTravelPhotoResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\TpTravelPhoto;
use App\Models\tp\comercial\TravelControl;
use Illuminate\Http\UploadedFile;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Throwable;

class TpTravelPhotoService extends BaseService
{
    // Aquí va la lógica del servicio para TpTravelPhoto
    protected $digitalFileService;

    public function __construct(DigitalFileService $digitalFileService)
    {
        $this->digitalFileService = $digitalFileService;
    }

    private $allowedTypes = ['start', 'end',
    'fuel', 'incident', 'invoice'];

    //tipos permitidos para imagenes
     private $allowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ];


    public function storeTravelPhoto(
        UploadedFile $file,
        int $dispatchId,
        int $driverId,
        string $typePhoto,
        array $metadata = []
    ){

        try{

            //validaciones iniciales

            $this->validateData($file, $dispatchId,$driverId, $typePhoto, $metadata);

            //subir archivo usando digitalfileservices

            $digitalFile = $this->uploadFileViaDigitalService($file, $dispatchId, $typePhoto);


            $photoData = [
                'dispatch_id' => $dispatchId,
                'driver_id' => $driverId,
                'digital_file_id' => $digitalFile->id,
                'photo_type' => $typePhoto,
                'latitude' => $metadata['latitude'] ?? null,
                'longitude' => $metadata['longitude'] ?? null,
                'user_agent' => $metadata['user_agent'] ?? null,
                'operating_system' => $metadata['operating_system'] ?? null,
                'browser' => $metadata['browser'] ?? null,
                'device_model' => $metadata['device_model'] ?? null,
                'notes' => $metadata['notes'] ?? null,
                'created_by' => auth()->id() ?? null,
            ];

            $photo = TpTravelPhoto::create($photoData);
            Log::info("Foto guardada en BD con ID: {$photo->id}");

            
            return new TpTravelPhotoResource($photo);
        
        }catch(Throwable $th){
            Log::error("Error en el servicio de fotos: ".$th->getMessage());
            throw new Exception("Error al guardar la captura: ".$th->getMessage());
        }

    }

    public function storeFromBase64(
        string $base64Image,
        int $dispatchId,
        int $driverId,
        string $typePhoto,
        array $metadata = []

    ){
        $tempFile = null;
        try{
            Log::info("Procesando foto base64 para viaje {$dispatchId}, tipo: {$typePhoto}");

            if(strpos($base64Image, 'base64,') !== false){
                $base64Image = explode('base64', $base64Image)[1];
            }

             //decodificar base64
            $imageData = base64_decode($base64Image);
            if($imageData === false){
                throw new Exception("Base64 invalido");
            }

            $tempFile = tmpfile();
            fwrite($tempFile, $imageData);
            $tempFilePath = stream_get_meta_data($tempFile)['uri'];

            // crear uploadedfile

            $uploadedFile = new UploadedFile(
                $tempFilePath,
                "travel_{$dispatchId}_{$typePhoto}_".time().".jpg",
                'image/jpeg',
                null,
                true
            );
            if($metadata['guardar_base64'] ?? false){
                $metadata['base64_completo'] = "data:image/jpeg;base64," .base64_encode($imageData);
            }

            $result = $this->storeTravelPhoto($uploadedFile,$dispatchId,$driverId,$typePhoto,$metadata);

            if($tempFile){
                fclose($tempFile);
            }

            return $result;

        }catch(Throwable $th){
             if($tempFile){
                fclose($tempFile);
            }
            Log::error("Error en guardar a base64: ". $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);
            throw new Exception("Error al procesar imagen base64: " . $th->getMessage());
        }

    }

    private function uploadFileViaDigitalService(UploadedFile $file, int $dispatchId,
    string $typePhoto)
    {
        try{

            $path = $this->generateDigitalFilePath($file,$dispatchId, $typePhoto);

            $digitalFile = $this->digitalFileService->store(
                $file,
                $path,
                'public',
                'tp_travel_photo'
            );

            Log::info("Archivo subido a S3 via DigitalFileService. ID: {$digitalFile->id}");

            return $digitalFile->resource;


        }catch(Throwable $th){
            Log::error("Error en el servicio UploadFileViaDigitalService: ".$th->getMessage());
            throw new Exception("Error al subir archivo a S3: ".$th->getMessage());
        }
    }

    private function generateDigitalFilePath(UploadedFile $file, int $dispatchId, string $typePhoto): string
    {
        $year = date('Y');
        $month = date('m');
        $timestamp = time();
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = "travel_{$dispatchId}_{$typePhoto}_{$timestamp}.{$extension}";

        return "tp/travel_photos/{$year}/{$month}/{$dispatchId}/{$typePhoto}/{$fileName}";
    }


    public function getPhotosByTravel(int $dispatchId, array $filters = [])
    {
        try{
            $query = TpTravelPhoto::with('digitalFile')
                        ->where('dispatch_id', $dispatchId);

            if(!empty($filters['photo_type'])){
                $query->where('photo_type', $filters['photo_type']);
            }

            if(!empty($filters['driver_id'])){
                $query->where('driver_id', $filters['driver_id']);
            }

            if(!empty($filters['begin_date'])){
                $query->where('created_at', '>=', $filters['begin_date']);
            }

            if(!empty($filters['finish_date'])){
                $query->where('created_at', '<=', $filters['finish_date']);
            }

            $photos = $query->orderBy('created_at', 'desc')->get();

            return TpTravelPhotoResource::collection($photos);

        }catch(Throwable $th){
            Log::error("Error en obtener las fotos del viaje: ".$th->getMessage());
            throw new Exception("Error al obtener fotos del viaje: ".$th->getMessage());
        }
    }

    public function deletePhoto(int $photoId)
    {
        try{
            $photo = TpTravelPhoto::findOrFail($photoId);


            if($photo->digital_file_id){
                $this->digitalFileService->destroy($photo->digital_file_id);
            }

            $photo->delete();

            Log::info("Foto Eliminada: {$photoId}, Archivo Digital: {$photo->digital_file_id}");

            return  [
                'mensaje' => 'Foto Eliminada Correctamente',
                'foto_id' => $photoId,
                'digital_file_id' => $photo->digital_file_id
            ];

        }catch(Throwable $th){
            Log::error("Error en eliminar la foto: ". $th->getMessage());
            throw new Exception("Error al eliminar la foto: ". $th->getMessage());

        }
    }


    //estadisticas de registro de fotos por viaje

    public function getStatsByTravel(int $dispatchId){
        try{

            $total = TpTravelPhoto::byTrip($dispatchId)->count();
            $byType = TpTravelPhoto::byTrip($dispatchId)
                        ->selectRaw('photo_type, COUNT(*) as cantidad')
                        ->groupBy('photo_type')
                        ->pluck('cantidad', 'photo_type')
                        ->toArray();

            $conGeolocation = TpTravelPhoto::byTrip($dispatchId)
                                ->withGeolocation()
                                ->count();
            
            return [
                'total_photos' => $total,
                'photos_by_type' => $byType,
                'with_geolocation' => $conGeolocation,
                'no_geolocation' =>$total - $conGeolocation
            ];

        }catch(Throwable $th){
             Log::error("Error en obtener las metricas: " . $th->getMessage());
            throw new Exception("Error al obtener estadísticas: " . $th->getMessage());
        }
    }

    private function deleteFromServer(string $path)
    {
        try{
            $disk = config('filesystems.default', 'local');
            $path = ltrim($path, '/');
            Storage::disk($disk)->delete($path);

        }catch(Throwable $th){
            Log::warning("No se puede eliminar de S3: {$path}. Error: ".$th->getMessage());
        }
    }



    private function validateData($file, $dispatchId,$driverId, $typePhoto, $metadata)
    {
         //validaciones de tipo de foto

        if(!in_array($typePhoto, $this->allowedTypes)){
            throw new Exception("Tipo de foto no valida. Permitidos: ".implode(',',$this->allowedTypes));

        }

        //validar archivo

        if(!$file instanceof UploadedFile){
            throw new Exception("Archivo no reconocido");
        }

        //validar mime
        $mimeType = $file->getClientMimeType();
        if(!in_array($mimeType, $this->allowedMimeTypes)){
            throw new Exception("Tipo de archivo no permitido. Permitidos: ".implode(', ', $this->allowedMimeTypes));
        }

        //validar tamaño del archivo
        $maxSize = 5 * 1024 * 1024; //5 mb
        if($file->getsize() > $maxSize){
            throw new Exception("El archivo es demasiado grande. Maximo 5MB");
        }

        //validar ids
        if($dispatchId <= 0){
            throw new Exception("ID de viaje invalido");
        }

        if($driverId <= 0){
            throw new Exception("ID de conductor invalido");
        }
    }



    public function list(Request $request, $id)
    {
        try{
            $travel = TravelControl::activos()->find($id);
             if(!$travel){
                return response()->json([
                    'message' => 'Viaje no encontrado o no disponible'
                ], 404);
            }

            $filtros = [
                'photo_type' => $request->get('photo_type'),
                'driver_id' => $request->get('driver_id'),
                'begin_date' => $request->get('begin_date'),
                'finish_date' => $request->get('finish_date')
            ];

            $photos = $this->getPhotosByTravel($id, $filtros);

            return $photos;

        }catch(Throwable $th){
            Log::error("Error en index fotos: " . $th->getMessage());
            throw new Exception("Error al obtener las fotos" . $th->getMessage());
        }
    }

    public function store(Request $data,$id)
    {
        try{

            $travel = TravelControl::activos()->find($id);

            if(!$travel){
                throw new Exception('Viaje no encontrado o no disponible');
            }
            $validated = $data->validate([
                'photo' => 'required|string',
                'photo_type' => 'required|in:start,end,fuel,incident,invoice',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'user_agent' => 'nullable|string',
                'operating_system' => 'nullable|string',
                'browser' => 'nullable|string',
                'device_model' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $userAgent = $validated['user_agent']  ?? $data->header('User-Agent');
            $deviceInfo = DeviceHelper::parseUserAgent($userAgent);

           $metadata = [
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'user_agent' => $userAgent,
                'operating_system' => $deviceInfo['operating_system'] ?? $validated['operating_system'] ?? null,
                'browser' => $deviceInfo['browser'] ?? $validated['browser'] ?? null,
                'device_model' => $deviceInfo['device_model'] ?? $validated['device_model'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ];

            $result = $this->storeFromBase64(
                $validated['photo'],
                $travel->id,
                $travel->conductor_id,
                $validated['photo_type'],
                $metadata
            );

            Log::info("Foto guardada para viaje {$id}", [
                'photo_type' => $data->photo_type,
                'user' => auth()->id()
            ]);

            return $result;

        }catch(Throwable $th)
        {
            Log::error("Error en el servicio de crear fotos" . $th->getMessage());
            throw new Exception('Error al crear la foto: '.$th->getMessage());
        }
    }


    public function show($id){
        try{

            $photo = TpTravelPhoto::find($id);

            if(!$photo){
                return response()->json([
                    'message' => 'Foto no encontrada'
                ], 404);
            }

            return new TpTravelPhotoResource($photo);

        }catch(Throwable $th){
            Log::error("Error en show foto: " . $th->getMessage());
            throw new Exception("Error en el servicio de obtener la foto: " . $th->getMessage());
        }
    }

    public function destroy(int $id){
        try{

            $photo = TpTravelPhoto::findOrFail($id);

            $user = auth()->user();

            $this->deleteFromServer($photo->path);

            $photo->delete();

            Log::info("Foto eliminada: {$id}");

            return [
                'mensaje' => 'Foto eliminada correctamente',
                'photo_id' => $id
            ];

        }catch(Throwable $th){
            Log::error("Error al eliminar la foto: ".$th->getMessage());
            throw new Exception("Error al eliminar la foto: ".$th->getMessage());
        }
    }

    public function photoStatistics(int $id){
        try{
            $travel = TpTravelPhoto::find($id);

            if(!$travel){
                return response()->json([
                    'message' => 'Viaje no encontrado'
                ], 400);
            }

            $statistics = $this->getStatsByTravel($id);

            return response()->json([
                'data' => $statistics,
                'travel' => [
                    'id' => $travel->id,
                    'driver' => $travel->driver->nombre_completo ?? 'N/A'
                ]
                ]);

        }catch(Throwable $th){
            Log::error("Error en estadisticas de las fotos: ".$e->getMessage());
            throw new Exception("Error al obtener estadisticas" . $th->getMessage());
        }
    }


}