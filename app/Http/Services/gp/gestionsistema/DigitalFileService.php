<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\DigitalFileResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\DigitalFile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function auth;
use function basename;
use function file_get_contents;
use function response;
use function time;

class DigitalFileService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      DigitalFile::class,
      $request,
      DigitalFile::filters,
      DigitalFile::sorts,
      DigitalFileResource::class,
    );
  }

  private function enrichImageData(array $data): array
  {
    $data['updater_user'] = auth()->user()->id;
    return $data;
  }

  public function find($id)
  {
    $file = DigitalFile::where('id', $id)->first();
    if (!$file) {
      throw new Exception('Imagen no encontrada');
    }
    return $file;
  }

  public function store($file, string $path = '/gp/images/test/', string $type = 'public', $model = null)
  {
    if ($model === null) {
      $model = (new DigitalFile())->getTable();
    }

    $fileName = $path . time() . '_' . $file->getClientOriginalName();

    $data['name'] = $fileName;
    $data['model'] = $model;
    $data['id_model'] = 0;
    $data['url'] = $this->uploadImage($file, $fileName, $type);
    $data['mimeType'] = $file->getClientMimeType();

    $newImage = DigitalFile::create($data);
    $newImage->id_model = $newImage->id;
    $newImage->save();

    return new DigitalFileResource($newImage);
  }


  public function storeMany($files, string $type = 'public')
  {
    $storedImages = [];
    foreach ($files as $file) {
      $newImage = $this->store($file, $type);
      $storedImages[] = $newImage;
    }
    return $storedImages;
  }

  public function show($id)
  {
    return new DigitalFileResource($this->find($id));
  }


  public function destroy($id)
  {
    $file = $this->find($id);

    Storage::disk('s3')->delete(ltrim($file->name, '/')); // quita el "/" inicial
    $file->delete();

    return response()->json(['message' => 'Imagen eliminada correctamente']);
  }


  public function uploadImage(\Illuminate\Http\UploadedFile $file, string $fileName, string $visibility = 'public')
  {
    // guarda el archivo en el Space
    Storage::disk('s3')->put(
      $fileName,
      file_get_contents($file->getRealPath()),
      ['visibility' => $visibility]
    );

    // devuelve la URL pública (o presigned si el bucket es privado)
    return Storage::disk('s3')->url($fileName);
  }


}
