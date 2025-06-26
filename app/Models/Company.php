<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'businessName',
        'email',
        'logo',
        'website',
        'phone',
        'address',
        'city'
    ];

    public function sedes()
    {
        return $this->hasMany(Sede::class, 'empresa_id', 'id');
    }
}
