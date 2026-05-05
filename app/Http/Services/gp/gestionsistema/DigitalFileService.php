<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\DigitalFileResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\DigitalFile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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


  public function uploadImage(UploadedFile $file, string $fileName, string $visibility = 'public')
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

  /**
   * Sube contenido raw (ej. PDF generado en memoria) a S3 y registra en gp_digital_files.
   *
   * @param string      $content    Contenido binario del archivo
   * @param string      $filename   Nombre base del archivo (ej. "assignment_1_2026-01-01.pdf")
   * @param string      $path       Directorio en S3 (con slash al final)
   * @param string      $visibility 'public' | 'private'
   * @param string      $mimeType   MIME type del archivo
   * @param string|null $model      Nombre de la tabla relacionada
   * @param int         $idModel    ID del registro relacionado
   */
  public function storeFromContent(
    string  $content,
    string  $filename,
    string  $path = '/gp/files/',
    string  $visibility = 'public',
    string  $mimeType = 'application/pdf',
    ?string $model = null,
    int     $idModel = 0
  ): DigitalFile {
    $s3Path = $path . time() . '_' . $filename;

    Storage::disk('s3')->put($s3Path, $content, ['visibility' => $visibility]);

    $url = Storage::disk('s3')->url($s3Path);

    $digitalFile = DigitalFile::create([
      'name'     => $s3Path,
      'model'    => $model ?? (new DigitalFile())->getTable(),
      'id_model' => $idModel ?: 0,
      'url'      => $url,
      'mimeType' => $mimeType,
    ]);

    if ($idModel === 0) {
      $digitalFile->id_model = $digitalFile->id;
      $digitalFile->save();
    }

    return $digitalFile;
  }


}
