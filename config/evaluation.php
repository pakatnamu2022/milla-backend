<?php

// Definir las constantes al inicio del archivo
const EVALUATION_PERSON_PENDING = 0;
const EVALUATION_PERSON_IN_PROGRESS = 1;
const EVALUATION_PERSON_COMPLETED = 2;

return [
  'typesEvaluation' => [
    0 => 'Evaluación de Objetivos',
    1 => 'Evaluación 180°',
    2 => 'Evaluación 360°',
  ],
  'typesParameter' => [
    'objectives' => 'Objetivos',
    'competences' => 'Competencias',
    'final' => 'Final',
  ],
  'statusEvaluation' => [
    0 => 'Programado',
    1 => 'En Progreso',
    2 => 'Completado',
  ],
  'evaluatorType' => [
    0 => 'Jefe',
    1 => 'Autoevaluación',
    2 => 'Compañeros',
    3 => 'Reportes',
  ],
  'statusEvaluationPerson' => [
    EVALUATION_PERSON_PENDING => 'Pendiente',
    EVALUATION_PERSON_IN_PROGRESS => 'En Progreso',
    EVALUATION_PERSON_COMPLETED => 'Completado',
  ]
];
