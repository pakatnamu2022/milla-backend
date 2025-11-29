<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreAppointmentPlanningRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'required',
        'string',
        'max:255',
      ],
      'delivery_date' => [
        'required',
        'date',
      ],
      'delivery_time' => [
        'required',
        'date_format:H:i',
      ],
      'date_appointment' => [
        'required',
        'date',
      ],
      'time_appointment' => [
        'required',
        'date_format:H:i',
      ],
      'full_name_client' => [
        'required',
        'string',
        'max:100',
      ],
      'email_client' => [
        'required',
        'string',
        'email',
        'max:100',
      ],
      'phone_client' => [
        'required',
        'string',
        'max:20',
      ],
      'type_operation_appointment_id' => [
        'required',
        'integer',
        'exists:ap_post_venta_masters,id,type=TIPO_OPERACION',
      ],
      'type_planning_id' => [
        'required',
        'integer',
        'exists:ap_post_venta_masters,id,type=TIPO_PLANIFICACION',
      ],
      'ap_vehicle_id' => [
        'required',
        'integer',
        'exists:ap_vehicles,id',
      ],
      'advisor_id' => [
        'required',
        'integer',
        'exists:rrhh_persona,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'description.required' => 'El campo descripción es obligatorio.',
      'description.string' => 'El campo descripción debe ser una cadena de texto.',
      'description.max' => 'El campo descripción no debe exceder los 255 caracteres.',

      'delivery_date.required' => 'El campo fecha de entrega es obligatorio.',
      'delivery_date.date' => 'El campo fecha de entrega debe ser una fecha válida.',

      'delivery_time.required' => 'El campo hora de entrega es obligatorio.',
      'delivery_time.date_format' => 'El campo hora de entrega debe tener el formato HH:MM.',

      'date_appointment.required' => 'El campo fecha de cita es obligatorio.',
      'date_appointment.date' => 'El campo fecha de cita debe ser una fecha válida.',

      'time_appointment.required' => 'El campo hora de cita es obligatorio.',
      'time_appointment.date_format' => 'El campo hora de cita debe tener el formato HH:MM.',

      'full_name_client.required' => 'El campo nombre completo del cliente es obligatorio.',
      'full_name_client.string' => 'El campo nombre completo del cliente debe ser una cadena de texto.',
      'full_name_client.max' => 'El campo nombre completo del cliente no debe exceder los 100 caracteres.',

      'email_client.required' => 'El campo correo electrónico del cliente es obligatorio.',
      'email_client.string' => 'El campo correo electrónico del cliente debe ser una cadena de texto.',
      'email_client.email' => 'El campo correo electrónico del cliente debe ser una dirección de correo válida.',
      'email_client.max' => 'El campo correo electrónico del cliente no debe exceder los 100 caracteres.',

      'phone_client.required' => 'El campo teléfono del cliente es obligatorio.',
      'phone_client.string' => 'El campo teléfono del cliente debe ser una cadena de texto.',
      'phone_client.max' => 'El campo teléfono del cliente no debe exceder los 20 caracteres.',

      'type_operation_appointment_id.required' => 'El campo tipo de operación de cita es obligatorio.',
      'type_operation_appointment_id.integer' => 'El campo tipo de operación de cita debe ser un entero.',
      'type_operation_appointment_id.exists' => 'El tipo de operación de cita seleccionado no es válido.',

      'type_planning_id.required' => 'El campo tipo de planificación es obligatorio.',
      'type_planning_id.integer' => 'El campo tipo de planificación debe ser un entero.',
      'type_planning_id.exists' => 'El tipo de planificación seleccionado no es válido.',

      'ap_vehicle_id.required' => 'El campo vehículo es obligatorio.',
      'ap_vehicle_id.integer' => 'El campo vehículo debe ser un entero.',
      'ap_vehicle_id.exists' => 'El vehículo seleccionado no es válido.',

      'advisor_id.required' => 'El campo asesor es obligatorio.',
      'advisor_id.integer' => 'El campo asesor debe ser un entero.',
      'advisor_id.exists' => 'El asesor seleccionado no es válido.',
    ];
  }
}
