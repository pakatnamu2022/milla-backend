<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;

class Person extends BaseModel
{
//    use LogsActivity;

  protected $table = "rrhh_persona";
  protected $primaryKey = 'id';

  protected $fillable = [
    'id',
    'vat',
    'nombre_completo'
  ];

  const filters = [
    'search' => ['nombre_completo', 'vat'],
    'vat' => 'like',
    'nombre_completo' => 'like',
  ];

  const sorts = [
    'vat',
    'nombre_completo',
  ];

  public function scopeWorking($query)
  {
    return $query
      ->where('status_deleted', 1)
      ->where('b_empleado', 1)
      ->where('status_id', 22);
  }


//    public function asignaciones()
//    {
//        return $this->hasMany(RrhhAsigAgente::class, 'id_agente');
//    }
//
//    public function usuarioSolicitaCredito()
//    {
//        return $this->hasOne(User::class, 'id', 'usuario_solicita_credito');
//    }
//
//
//    public function usuarioApruebaCredito()
//    {
//        return $this->hasOne(User::class, 'id', 'usuario_aprobo_credito');
//    }
//
//    public function tipodocumento()
//    {
//        return $this->hasOne(TipoDocumento::class, 'id', 'tipo_doc');
//    }
//
//    public function sistema_pensiones()
//    {
//        return $this->hasOne(Sispensiones::class, 'id', 'sis_pensiones_id');
//    }
//
//    public function tipotrabajador()
//    {
//        return $this->hasOne(TipoEmpleado::class, 'id', 'tipo_trabajador_id');
//    }
//
//    public function estudios()
//    {
//        return $this->hasOne(Estudios::class, 'id', 'estudios_id');
//    }
//
  public function sede()
  {
    return $this->hasOne(Sede::class, 'id', 'sede_id');
  }
//
//    public function area()
//    {
//        return $this->hasOne(Area::class, 'id', 'area_id');
//    }
//

  public function evaluationDetails()
  {
    return $this->hasMany(EvaluationPersonDetail::class, 'person_id');
  }

  public function boss()
  {
    return $this->hasOne(Person::class, 'id', 'jefe_id');
  }

  public function position()
  {
    return $this->hasOne(Position::class, 'id', 'cargo_id');
  }
//
//    public function depubigeo()
//    {
//        return $this->hasOne(DepUbigeo::class, 'id', 'dep_ubigeo_id');
//    }
//
//    public function proceso_postulacion()
//    {
//        return $this->hasOne(ProcesoPostulacion::class, 'id', 'proceso_postulacion_id');
//    }
//

//

//
//    public function direcciones()
//    {
//        return $this->hasMany(fac_direcciones::class, 'cliente_id', 'id');
//    }
//
//    public function parientes()
//    {
//        return $this->hasMany(rrhh_parientes::class, 'persona_id', 'id');
//    }
//
//
//    public function experiencia()
//    {
//        return $this->hasMany(rrhh_experiencia_laboral::class, 'persona_id', 'id');
//    }
//
//    public function status()
//    {
//        return $this->hasOne(Status::class, 'id', 'status_id');
//    }
//
//    public function tipo_cliente()
//    {
//        return $this->hasOne(DepTipoCliente::class, 'id', 'dep_tipo_cliente_id');
//    }
//
//    public function tipoDoc()
//    {
//        return $this->hasOne(TipoDocumento::class, 'id', 'tipo_documento_id');
//    }
//    public function relacionado()
//    {
//        return $this->hasOne(Persona::class, 'id', 'persona_principal_id');
//    }
//
//
//    public function ref1()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_1');
//    }
//    public function ref7()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_7');
//    }
//    public function ref2()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_2');
//    }
//    public function ref3()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_3');
//    }
//    public function ref4()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_4');
//    }
//    public function ref5()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_5');
//    }
//    public function ref6()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_6');
//    }
//    public function ref10()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_10');
//    }
//    public function ref11()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_11');
//    }
//    public function ref12()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_12');
//    }
//    public function ref13()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_13');
//    }
//    public function ref14()
//    {
//        return $this->hasOne(DepReferenciaAdicional::class, 'id', 'referencia_adicional_14');
//    }
//
//    public function facturas()
//    {
//        return $this->hasMany(FacturaCab::class, 'cliente_id');
//    }
//
//
//    // Relación con cotizaciones como cliente
//    public function cotizacionesComoCliente()
//    {
//        return $this->hasMany(ap_cotizaciones_venta::class, 'cliente_id');
//    }
//
//    // Relación con cotizaciones como vendedor
//    public function cotizacionesComoVendedor()
//    {
//        return $this->hasMany(ap_cotizaciones_venta::class, 'vendedor_id');
//    }
//
//    // Relación con cotizaciones como titular VN
//    public function cotizacionesComoTitularVn()
//    {
//        return $this->hasMany(ap_cotizaciones_venta::class, 'cta_titular_vn_id');
//    }
//
//    public function getActivitylogOptions(): LogOptions
//    {
//        return LogOptions::defaults()
//            ->logAll();
//    }
}
