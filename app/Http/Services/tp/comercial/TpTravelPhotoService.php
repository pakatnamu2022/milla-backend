<?php

namespace App\Http\Services\tp\comercial;

use App\Helpers\DeviceHelper;
use App\Http\Resources\tp\comercial\TpTravelPhotoResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\TpTravelPhoto;
use App\Models\tp\comercial\TravelControl;
use App\Models\tp\comercial\TravelPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;

class TpTravelPhotoService extends BaseService
{
    // Aquí va la lógica del servicio para TpTravelPhoto

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

            //generar informacion del archivo
            $nameFile = $this->generateFileName($file, $dispatchId, $typePhoto);

            $path = $this->generatePath($dispatchId, $typePhoto, $nameFile);

            Log::info("Subiendo foto al servidor: {$path}");
            $urlPublic = $this->uploadTo($file, $path);

            $photoData = [
                'dispatch_id' => $dispatchId,
                'driver_id' => $driverId,
                'photo_type' => $typePhoto,
                'file_name' => $nameFile,
                'path' => $path,
                'public_url' =>$urlPublic,
                'mime_type' => $file->getClientMimeType(),
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
        
        }catch(Exception $e){
            Log::error("Error en el servicio de fotos: " . $e->getMessage(), [
                'dispatch_id' => $dispatchId,
                'driver_id' => $driverId,
                'type_photo' => $typePhoto,
                'error' => $e->getTraceAsString()
            ]);
            throw new Exception("Error al guardar la captura: " . $e->getMessage());


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

        }catch(Exception $e){
             if($tempFile){
                fclose($tempFile);
            }

            Log::error("Error en guardar a base64: ". $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Error al procesar imagen base64: " . $e->getMessage());

        }

    }


    public function getPhotosByTravel(int $dispatchId, array $filters = [])
    {
        try{
            $query = TpTravelPhoto::byTrip($dispatchId);

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

        }catch(Exception $e){
            Log::error("Error en obtener las fotos del viaje: ".$e->getMessage());
            throw new Exception("Error al obtener fotos del viaje: ".$e->getMessage());
        }
    }

    public function deletePhoto(int $photoId)
    {
        try{
            $photo = TpTravelPhoto::findOrFail($photoId);
            $this->deleteFromServer($photo->path);

            $photo->delete();

            Log::info("Foto Eliminada: {$photoId}");

            return  [
                'mensaje' => 'Foto Eliminada Correctamente',
                'foto_id' => $photoId
            ];

        }catch(Exception $e){
            Log::error("Error en eliminar la foto: ". $e->getMessage());
            throw new Exception("Error al eliminar la foto: ". $e->getMessage());

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

        }catch(Exception $e){
             Log::error("Error en obtener las metricas: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }

    private function deleteFromServer(string $path)
    {
        try{
            $disk = config('filesystems.default', 'local');
            $path = ltrim($path, '/');
            Storage::disk($disk)->delete($path);

        }catch(Exception $e){
            Log::warning("No se puede eliminar de S3: {$path}. Error: ".$e->getMessage());
        }
    }



    private function generateFileName(UploadedFile $file, int $dispatchId, string $typePhoto)
    {
        $timestamp = time();
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $baseName = Str::slug("travel-{$dispatchId}-{$typePhoto}", '-');

        return "{$baseName}-{$timestamp}.{$extension}";
    }



    private function uploadTo(UploadedFile $file, string $path)
    {
        //antes
        $disk = config('filesystems.default', 'local');
        $path = ltrim($path, '/');

        // nueva configuracion
        // $disk = 's3';
        // $path = ltrim($path, '/');
        
        Storage::disk($disk)->put(
            $path,
            file_get_contents($file->getRealPath()),
            [
                'visibility' => 'public',
                'ContentType' => $file->getMimeType()
            ]
        );

        return Storage::disk($disk)->url($path);
    }

    private function generatePath(int $dispatchId, string $typePhoto, string $nameFile)
    {
        $year = date('Y');
        $month = date('m');

        return "tp/viajes/{$year}/{$month}/{$dispatchId}/{$typePhoto}/{$nameFile}";
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

    private function formatResponse(TpTravelPhoto $photo)
    {
        return [
            'id' => $photo->id,
            'dispatch_id' => $photo->dispatch_id,
            'driver_id' => $photo->driver_id,
            'photo_type'=> $photo->photo_type,  //inicio, fin, combustible, incidente
            'file_name' => $photo->file_name,
            'public_url'=> $photo->public_url,
            'mime_type' => $photo->mime_type,
            'latitude' =>  $photo->latitude,
            'longitude'=>  $photo->longitude,
            'user_agent' => $photo->user_agent,
            'operating_system' => $photo->operating_system,
            'browser' => $photo->browser,
            'device_model' => $photo->device_model,
            'notes' => $photo->notes,
            'created_at' => $photo->created_at->format('Y-m-d H:i:s'),
            'has_geolocation' => !empty($photo->latitude) && !empty($photo->longitude),
        ];
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

        }catch(\Exception $e){
            Log::error("Error en index fotos: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al obtener las fotos',
                'error' => $e->getMessage()
            ], 500);

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

        }catch(Exception $e)
        {
            throw new Exception('Error al crear la foto: '.$e->getMessage());
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

        }catch(Exception $e){
            Log::error("Error en show foto: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al obtener la foto',
                'error' => $e->getMessage()
            ], 500);
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

        }catch(Exception $e){
            Log::error("Error al eliminar la foto: ".$e->getMessage());
            throw new Exception("Error al eliminar la foto: ".$e->getMessage());
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

        }catch(Exception $e){
            Log::error("Error en estadisticas de las fotos: ".$e->getMessage());

            return response()->json([
                'message' => 'Error al obtener estadisticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // public function storeMultiple(Request $request, $id){
    //     $validator = Validator::make($request->all(), [
    //         'fotos' => 'required|array|min:1|max:10',
    //         'fotos.*.foto' => 'required|string',
    //         'fotos.*.tipo_foto' => 'required|in:inicio,fin,combustible,incidente,comprobante',
    //         'fotos.*.latitud' => 'nullable|numeric|between:-90,90',
    //         'fotos.*.longitud' => 'nullable|numeric|between:-180,180'
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'message' => 'Error de validación',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try{
    //         $viaje = TravelControl::activos()->find($id);
    //         if (!$viaje) {
    //             return response()->json([
    //                 'message' => 'Viaje no encontrado o no disponible'
    //             ], 404);
    //         }
    //          $resultados = [];
    //         $errores = [];
            
    //         foreach ($request->fotos as $index => $fotoData) {
    //             try {
    //                 $metadata = [
    //                     'latitud' => $fotoData['latitud'] ?? null,
    //                     'longitud' => $fotoData['longitud'] ?? null,
    //                     'dispositivo_id' => $fotoData['dispositivo_id'] ?? null,
    //                     'observaciones' => $fotoData['observaciones'] ?? null,
    //                     'guardar_base64' => $fotoData['guardar_base64'] ?? false
    //                 ];
                    
    //                 $resultado = $this->photoService->storeFromBase64(
    //                     $fotoData['foto'],
    //                     $viaje->id,
    //                     $viaje->conductor_id,
    //                     $fotoData['tipo_foto'],
    //                     $metadata
    //                 );
                    
    //                 $resultados[] = [
    //                     'indice' => $index,
    //                     'estado' => 'exitoso',
    //                     'data' => $resultado
    //                 ];
                    
    //             } catch (\Exception $e) {
    //                 $errores[] = [
    //                     'indice' => $index,
    //                     'estado' => 'error',
    //                     'error' => $e->getMessage()
    //                 ];
                    
    //                 Log::warning("Error en foto multiple {$index}: " . $e->getMessage());
    //             }
    //         }
            
    //         $totalExitosos = count(array_filter($resultados, fn($r) => $r['estado'] === 'exitoso'));
    //         $totalErrores = count($errores);
            
    //         return response()->json([
    //             'message' => "{$totalExitosos} fotos guardadas exitosamente, {$totalErrores} con errores",
    //             'data' => [
    //                 'exitosos' => $resultados,
    //                 'errores' => $errores
    //             ],
    //             'meta' => [
    //                 'total_procesadas' => count($request->fotos),
    //                 'exitosos' => $totalExitosos,
    //                 'errores' => $totalErrores
    //             ]
    //         ], $totalErrores === 0 ? 201 : 207);

    //     }catch(\Exception $e){

    //     }
    // }



}