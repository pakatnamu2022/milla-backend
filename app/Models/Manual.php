<?php

namespace App\Models;

use App\Models\gp\gestionsistema\DigitalFile;
use App\Models\gp\gestionsistema\View;

class Manual extends BaseModel
{
  protected $table = 'manuals';

  protected $fillable = [
    'vista_id',
    'digital_file_id',
    'company_slug',
    'module_slug',
    'title',
    'description',
    'order',
  ];

  const filters = [
    'vista_id'     => '=',
    'company_slug' => '=',
    'search'       => ['title', 'description'],
  ];

  const sorts = [
    'order'      => 'asc',
    'created_at' => 'desc',
  ];

  public function vista()
  {
    return $this->belongsTo(View::class, 'vista_id');
  }

  public function digitalFile()
  {
    return $this->belongsTo(DigitalFile::class, 'digital_file_id');
  }
}
