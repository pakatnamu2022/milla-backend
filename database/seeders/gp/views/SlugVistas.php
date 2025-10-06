<?php

namespace Database\Seeders\gp\views;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlugVistas extends Seeder
{
  public function run(): void
  {
    $slugs = [];

    DB::table('config_vista')->get()->each(function ($item) use (&$slugs) {
      if (!$item->descripcion) return;

      $baseSlug = Str::slug($item->descripcion);
      $slug = $baseSlug;
      $count = 1;

      while (in_array($slug, $slugs)) {
        $slug = $baseSlug . '-' . $count++;
      }

      $slugs[] = $slug;

      DB::table('config_vista')
        ->where('id', $item->id)
        ->update(['slug' => $slug]);
    });
  }

}
