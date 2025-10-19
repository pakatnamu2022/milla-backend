<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SunatConcepts extends Model
{
    use softDeletes;

    protected $table = 'sunat_concepts';

    protected $fillable = [
        'code',
        'description',
        'type',
    ];
}
