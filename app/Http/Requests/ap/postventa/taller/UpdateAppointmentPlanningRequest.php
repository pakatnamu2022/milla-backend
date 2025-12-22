<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentPlanningRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'nullable',
        'string',
        'max:255',
      ],
      'delivery_date' => [
        'nullable',
        'date',
      ],
      'delivery_time' => [
        'nullable',
        'date_format:H:i',
      ],
      'date_appointment' => [
        'nullable',
        'date',
      ],
      'time_appointment' => [
        'nullable',
        'date_format:H:i',
      ],
      'num_doc_client' => [
        'required',
        'string',
        'regex:/^(\d{8}|\d{11})$/',
      ],
      'full_name_client' => [
        'nullable',
        'string',
        'max:100',
      ],
      'email_client' => [
        'nullable',
        'string',
        'email',
        'max:100',
      ],
      'phone_client' => [
        'nullable',
        'string',
        'max:20',
      ],
      'type_operation_appointment_id' => [
        'nullable',
        'integer',
        Rule::exists('ap_post_venta_masters', 'id')
          ->where('type', 'TIPO_OPERACION'),
      ],
      'type_planning_id' => [
        'nullable',
        'integer',
        Rule::exists('ap_post_venta_masters', 'id')
          ->where('type', 'TIPO_PLANIFICACION'),
      ],
      'ap_vehicle_id' => [
        'required',
        'integer',
        'exists:ap_vehicles,id',
      ],
      'sede_id' => [
        'nullable',
        'integer',
        'exists:config_sede,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'description.string' => 'El campo descripción debe ser una cadena de texto.',
      'description.max' => 'El campo descripción no debe exceder los 255 caracteres.',

      'delivery_date.date' => 'El campo fecha de entrega debe ser una fecha válida.',

      'delivery_time.date_format' => 'El campo hora de entrega debe tener el formato HH:MM.',

      'date_appointment.date' => 'El campo fecha de cita debe ser una fecha válida.',

      'time_appointment.date_format' => 'El campo hora de cita debe tener el formato HH:MM.',

      'num_doc_client.required' => 'El campo número de documento del cliente es obligatorio.',
      'num_doc_client.string' => 'El campo número de documento del cliente debe ser una cadena de texto.',
      'num_doc_client.regex' => 'El número de documento debe tener 8 o 11 dígitos.',

      'full_name_client.string' => 'El campo nombre completo del cliente debe ser una cadena de texto.',
      'full_name_client.max' => 'El campo nombre completo del cliente no debe exceder los 100 caracteres.',

      'email_client.string' => 'El campo correo electrónico del cliente debe ser una cadena de texto.',
      'email_client.email' => 'El campo correo electrónico del cliente debe ser una dirección de correo válida.',
      'email_client.max' => 'El campo correo electrónico del cliente no debe exceder los 100 caracteres.',

      'phone_client.string' => 'El campo teléfono del cliente debe ser una cadena de texto.',
      'phone_client.max' => 'El campo teléfono del cliente no debe exceder los 20 caracteres.',

      'type_operation_appointment_id.integer' => 'El campo tipo de operación de cita debe ser un entero.',
      'type_operation_appointment_id.exists' => 'El tipo de operación de cita seleccionado no es válido.',

      'type_planning_id.integer' => 'El campo tipo de planificación debe ser un entero.',
      'type_planning_id.exists' => 'El tipo de planificación seleccionado no es válido.',

      'ap_vehicle_id.integer' => 'El campo vehículo debe ser un entero.',
      'ap_vehicle_id.exists' => 'El vehículo seleccionado no es válido.',

      'sede_id.integer' => 'El campo sede debe ser un entero.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }
}
