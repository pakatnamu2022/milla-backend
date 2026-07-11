<?php
namespace App\Models\tp\configuracionComercial\vehiculo;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoVehiculo extends Model
{
    use HasFactory;

    protected $table = 'op_tipo_vehiculo';
    public $timestamps = true;

    protected $fillable = [
        'descripcion',
        'write_id',
        'status_deleted'
    ];
     protected $casts = [
        'id' => 'integer',
        'write_id' => 'integer',
        'status_deleted' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
     // Scope para activos
    public function scopeActive($query)
    {
        return $query->where('status_deleted', 1);
    }
       // Scope para búsqueda
    public function scopeSearch($query, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function($q) use ($searchTerm) {
            $q->where('descripcion', 'like', "%{$searchTerm}%");
        });
    }
     // Relación con el usuario que lo creó
    public function creator()
    {
        return $this->belongsTo(User::class, 'write_id');
    }

}