<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use Illuminate\Database\Eloquent\Model;

/**
 * Mensajes automáticos parametrizables por etapa del proceso de selección
 * (rrhh_mensaje_etapa_proceso), enviados al cliente interno (solicitante /
 * jefatura) que solicitó la posición. Editables por Gestión Humana.
 */
class ProcessStageMessageTemplate extends Model
{
  protected $table = 'rrhh_mensaje_etapa_proceso';

  const ETAPA_CREADO      = 'creado';
  const ETAPA_PAUSADO     = 'pausado';
  const ETAPA_ENTREVISTAS = 'entrevistas';
  const ETAPA_SELECCIONADO = 'seleccionado';
  const ETAPA_CERRADO     = 'cerrado';

  const ETAPA_LABELS = [
    self::ETAPA_CREADO       => 'Proceso creado',
    self::ETAPA_PAUSADO      => 'Proceso pausado',
    self::ETAPA_ENTREVISTAS  => 'Entrevistas iniciadas',
    self::ETAPA_SELECCIONADO => 'Candidato seleccionado',
    self::ETAPA_CERRADO      => 'Proceso cerrado',
  ];

  protected $fillable = ['etapa', 'asunto', 'contenido', 'activo'];

  protected $casts = [
    'activo' => 'boolean',
  ];
}
