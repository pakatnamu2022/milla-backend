<?php

namespace App\Http\Utils;

use App\Models\gp\gestionsistema\District;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;

class Helpers
{
  /**
   * Obtiene el año actual
   */
  public static function getCurrentYear(): int
  {
    return Carbon::now()->year;
  }

  /**
   * Valida si una persona es mayor de edad
   */
  public static function isAdult(string $birthDate, int $adultAge = 18): bool
  {
    $birth = Carbon::parse($birthDate);
    $age = $birth->diffInYears(Carbon::now());

    return $age >= $adultAge;
  }

  public static function getDistrictFullLocation(int $districtId): ?string
  {
    $district = District::with('province.department')
      ->find($districtId);

    if (!$district) {
      return null;
    }

    return $district->name . ' - ' .
      $district->province->name . ' - ' .
      $district->province->department->name;
  }

  /**
   * Convierte una cadena base64 a un objeto UploadedFile
   * Automáticamente recorta los espacios en blanco/transparentes de la imagen
   */
  public static function base64ToUploadedFile(string $base64String, string $filename): UploadedFile
  {
    // Remover el prefijo data:image/png;base64, si existe
    if (strpos($base64String, 'data:image') !== false) {
      $base64String = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
    }

    // Decodificar base64
    $fileData = base64_decode($base64String);

    // Crear imagen desde el string
    $image = imagecreatefromstring($fileData);

    if ($image === false) {
      throw new Exception('No se pudo procesar la imagen');
    }

    // Recortar espacios en blanco/transparentes
    $trimmedImage = self::trimImage($image);

    // Guardar imagen recortada en archivo temporal
    $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '_' . $filename;
    imagepng($trimmedImage, $tempFilePath);

    // Liberar memoria
    imagedestroy($image);
    imagedestroy($trimmedImage);

    // Crear un UploadedFile desde el archivo temporal
    return new UploadedFile(
      $tempFilePath,
      $filename,
      'image/png',
      null,
      true // test mode - permite crear UploadedFile desde ruta
    );
  }

  /**
   * Recorta los espacios en blanco/transparentes de una imagen
   */
  public static function trimImage($image)
  {
    $width = imagesx($image);
    $height = imagesy($image);

    // Encontrar límites del contenido
    $minX = $width;
    $minY = $height;
    $maxX = 0;
    $maxY = 0;

    for ($y = 0; $y < $height; $y++) {
      for ($x = 0; $x < $width; $x++) {
        $alpha = (imagecolorat($image, $x, $y) >> 24) & 0xFF;

        // Si el pixel no es completamente transparente
        if ($alpha < 127) {
          if ($x < $minX) $minX = $x;
          if ($x > $maxX) $maxX = $x;
          if ($y < $minY) $minY = $y;
          if ($y > $maxY) $maxY = $y;
        }
      }
    }

    // Si no se encontró contenido, devolver imagen original
    if ($minX >= $maxX || $minY >= $maxY) {
      return $image;
    }

    // Agregar un pequeño padding (5px)
    $padding = 5;
    $minX = max(0, $minX - $padding);
    $minY = max(0, $minY - $padding);
    $maxX = min($width - 1, $maxX + $padding);
    $maxY = min($height - 1, $maxY + $padding);

    // Calcular nuevas dimensiones
    $newWidth = $maxX - $minX + 1;
    $newHeight = $maxY - $minY + 1;

    // Crear nueva imagen con las dimensiones recortadas
    $trimmedImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preservar transparencia
    imagealphablending($trimmedImage, false);
    imagesavealpha($trimmedImage, true);
    $transparent = imagecolorallocatealpha($trimmedImage, 0, 0, 0, 127);
    imagefill($trimmedImage, 0, 0, $transparent);

    // Copiar la porción recortada
    imagecopy($trimmedImage, $image, 0, 0, $minX, $minY, $newWidth, $newHeight);

    return $trimmedImage;
  }
}
