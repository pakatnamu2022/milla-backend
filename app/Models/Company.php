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

    const filters = [
        'serach' => [
            'name',
            'businessName',
            'email',
            'website',
            'phone',
            'address',
            'city'
        ],
        'name' => 'like',
        'businessName' => 'like',
        'email' => 'like',
        'website' => 'like',
        'phone' => 'like',
        'address' => 'like',
        'city' => 'like',
    ];

    const sorts = [
        'name' => 'asc',
        'businessName' => 'asc',
        'email' => 'asc',
        'website' => 'asc',
        'phone' => 'asc',
        'address' => 'asc',
        'city' => 'asc',
    ];

    public function sedes()
    {
        return $this->hasMany(Sede::class, 'empresa_id', 'id');
    }
}
