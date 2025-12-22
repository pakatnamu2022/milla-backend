<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\taller\AppointmentPlanning;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
      'num_doc_client' => [
        'required',
        'string',
        'regex:/^(\d{8}|\d{11})$/',
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
        Rule::exists('ap_post_venta_masters', 'id')
          ->where('type', 'TIPO_OPERACION'),
      ],
      'type_planning_id' => [
        'required',
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
        'required',
        'integer',
        'exists:config_sede,id',
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

      'num_doc_client.required' => 'El campo número de documento del cliente es obligatorio.',
      'num_doc_client.string' => 'El campo número de documento del cliente debe ser una cadena de texto.',
      'num_doc_client.regex' => 'El número de documento debe tener 8 o 11 dígitos.',

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

      'sede_id.required' => 'El campo sede es obligatorio.',
      'sede_id.integer' => 'El campo sede debe ser un entero.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }

  public function withValidator(Validator $validator): void
  {
    $validator->after(function (Validator $validator) {
      $dateAppointment = $this->input('date_appointment');
      $timeAppointment = $this->input('time_appointment');
      $deliveryDate = $this->input('delivery_date');
      $deliveryTime = $this->input('delivery_time');

      if ($dateAppointment && $timeAppointment && $deliveryDate && $deliveryTime) {
        $appointmentDateTime = Carbon::parse("$dateAppointment $timeAppointment");
        $deliveryDateTime = Carbon::parse("$deliveryDate $deliveryTime");

        // Validar que la entrega sea posterior a la cita
        if ($deliveryDateTime->lte($appointmentDateTime)) {
          $validator->errors()->add(
            'delivery_date',
            'La fecha y hora de entrega debe ser posterior a la fecha y hora de la cita.'
          );
          $validator->errors()->add(
            'delivery_time',
            'La fecha y hora de entrega debe ser posterior a la fecha y hora de la cita.'
          );
        }

        // Validar que no exista otra cita en esa fecha y hora
        $existingAppointment = AppointmentPlanning::where('date_appointment', $dateAppointment)
          ->where('time_appointment', $timeAppointment)
          ->when($this->route('id'), function ($query, $id) {
            return $query->where('id', '!=', $id);
          })
          ->exists();

        if ($existingAppointment) {
          $validator->errors()->add(
            'date_appointment',
            'Ya existe una cita programada para esta fecha y hora.'
          );
          $validator->errors()->add(
            'time_appointment',
            'Ya existe una cita programada para esta fecha y hora.'
          );
        }

        // Validar que no exista otra entrega en esa fecha y hora
        $existingDelivery = AppointmentPlanning::where('delivery_date', $deliveryDate)
          ->where('delivery_time', $deliveryTime)
          ->when($this->route('id'), function ($query, $id) {
            return $query->where('id', '!=', $id);
          })
          ->exists();

        if ($existingDelivery) {
          $validator->errors()->add(
            'delivery_date',
            'Ya existe una entrega programada para esta fecha y hora.'
          );
          $validator->errors()->add(
            'delivery_time',
            'Ya existe una entrega programada para esta fecha y hora.'
          );
        }
      }
    });
  }

}
