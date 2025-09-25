<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreBusinessPartnersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'first_name' => 'nullable|string|max:255',
      'middle_name' => 'nullable|string|max:255',
      'paternal_surname' => 'nullable|string|max:255',
      'maternal_surname' => 'nullable|string|max:255',
      'full_name' => 'required|string|max:255',
      'birth_date' => 'nullable|date',
      'nationality' => 'required|string|in:NACIONAL,EXTRANJERO',
      'num_doc' => [
        'required',
        'string',
        'max:20',
        Rule::unique('business_partners', 'num_doc')
          ->whereNull('deleted_at'),
      ],
      'spouse_num_doc' => 'nullable|string|max:20',
      'spouse_full_name' => 'nullable|string|max:255',
      'direction' => 'nullable|string|max:500',
      'legal_representative_num_doc' => 'nullable|string|max:20',
      'legal_representative_name' => 'nullable|string|max:255',
      'legal_representative_paternal_surname' => 'nullable|string|max:255',
      'legal_representative_maternal_surname' => 'nullable|string|max:255',
      'email' => [
        'nullable',
        'email:rfc,dns',
        'max:255'
      ],
      'secondary_email' => 'nullable|email|max:255',
      'phone' => 'nullable|string|max:20',
      'secondary_phone' => 'nullable|string|max:20',
      'secondary_phone_contact_name' => 'nullable|string|max:255',
      'driver_num_doc' => 'nullable|string|max:20',
      'driver_full_name' => 'nullable|string|max:255',
      'driving_license' => 'nullable|string|max:50',
      'driving_license_issue_date' => 'nullable|date|before_or_equal:today',
      'driving_license_expiration_date' => 'nullable|date|after:driving_license_issue_date',
      'status_license' => 'nullable|string|max:100',
      'restriction' => 'nullable|string|max:255',
      'driving_license_type_id' => 'nullable|integer|exists:ap_commercial_masters,id',
      'origin_id' => 'required|integer|exists:ap_commercial_masters,id',
      'tax_class_type_id' => 'required|integer|exists:tax_class_types,id',
      'type_road_id' => 'nullable|integer|exists:ap_commercial_masters,id',
      'type_person_id' => 'required|integer|exists:ap_commercial_masters,id',
      'district_id' => 'required|integer|exists:district,id',
      'document_type_id' => 'required|integer|exists:ap_commercial_masters,id',
      'person_segment_id' => 'required|integer|exists:ap_commercial_masters,id',
      'marital_status_id' => 'nullable|integer|exists:ap_commercial_masters,id',
      'gender_id' => 'nullable|integer|exists:ap_commercial_masters,id',
      'activity_economic_id' => 'required|integer|exists:ap_commercial_masters,id',
      'company_id' => 'required|integer|exists:companies,id',
    ];
  }

  public function messages(): array
  {
    return [
      'first_name.string' => 'El nombre debe ser una cadena de texto.',
      'first_name.max' => 'El nombre no debe exceder los 255 caracteres.',

      'middle_name.string' => 'El nombre debe ser una cadena de texto.',
      'middle_name.max' => 'El nombre no debe exceder los 255 caracteres.',

      'middle_name.string' => 'El segundo nombre debe ser una cadena de texto.',
      'middle_name.max' => 'El segundo nombre no debe exceder los 255 caracteres.',
      'middle_name.required' => 'El segundo nombre es obligatorio.',

      'paternal_surname.string' => 'El apellido paterno debe ser una cadena de texto.',
      'paternal_surname.max' => 'El apellido paterno no debe exceder los 255 caracteres.',

      'maternal_surname.string' => 'El apellido materno debe ser una cadena de texto.',
      'maternal_surname.max' => 'El apellido materno no debe exceder los 255 caracteres.',

      'full_name.required' => 'El nombre completo es obligatorio.',
      'full_name.string' => 'El nombre completo debe ser una cadena de texto.',
      'full_name.max' => 'El nombre completo no debe exceder los 255 caracteres.',

      'birth_date.date' => 'La fecha de nacimiento debe ser una fecha válida.',

      'nationality.required' => 'La nacionalidad es obligatoria.',
      'nationality.in' => 'La nacionalidad debe ser NACIONAL o EXTRANJERO.',

      'num_doc.required' => 'El número de documento es obligatorio.',
      'num_doc.string' => 'El número de documento debe ser una cadena de texto.',
      'num_doc.max' => 'El número de documento no debe exceder los 20 caracteres.',
      'num_doc.unique' => 'El número de documento ya está registrado para esta empresa.',

      'spouse_num_doc.max' => 'El número de documento del cónyuge no debe exceder los 20 caracteres.',
      'spouse_full_name.max' => 'El nombre completo del cónyuge no debe exceder los 255 caracteres.',

      'email.email' => 'El email debe tener un formato válido.',
      'secondary_email.email' => 'El email secundario debe tener un formato válido.',

      'driver_num_doc.max' => 'El número de documento del conductor no debe exceder los 20 caracteres.',
      'driver_full_name.max' => 'El nombre completo del conductor no debe exceder los 255 caracteres.',
      'driving_license_issue_date.date' => 'La fecha de emisión de la licencia debe ser una fecha válida.',
      'driving_license_issue_date.before_or_equal' => 'Ingrese la fecha de expedición.',
      'driving_license_expiration_date.date' => 'La fecha de vencimiento de la licencia debe ser una fecha válida.',
      'driving_license_expiration_date.after' => 'La fecha de vencimiento debe ser posterior a la fecha de emisión.',

      'status_license.max' => 'El estado de la licencia no debe exceder los 100 caracteres.',
      'restriction.max' => 'La restricción no debe exceder los 255 caracteres.',

      'driving_license_type_id.exists' => 'El tipo de licencia de conducir seleccionado no existe.',

      'origin_id.required' => 'El origen es obligatorio.',
      'origin_id.exists' => 'El origen seleccionado no existe.',

      'tax_class_type_id.required' => 'El tipo de clase tributaria es obligatorio.',
      'tax_class_type_id.exists' => 'El tipo de clase tributaria seleccionado no existe.',

      'type_person_id.required' => 'El tipo de persona es obligatorio.',
      'type_person_id.exists' => 'El tipo de persona seleccionado no existe.',

      'district_id.required' => 'El distrito es obligatorio.',
      'district_id.exists' => 'El distrito seleccionado no existe.',

      'document_type_id.required' => 'El tipo de documento es obligatorio.',
      'document_type_id.exists' => 'El tipo de documento seleccionado no existe.',

      'person_segment_id.required' => 'El segmento de persona es obligatorio.',
      'person_segment_id.exists' => 'El segmento de persona seleccionado no existe.',

      'marital_status_id.exists' => 'El estado civil seleccionado no existe.',
      
      'gender_id.exists' => 'El género seleccionado no existe.',

      'activity_economic_id.required' => 'La actividad económica es obligatoria.',
      'activity_economic_id.exists' => 'La actividad económica seleccionada no existe.',

      'company_id.required' => 'La empresa es obligatoria.',
      'company_id.exists' => 'La empresa seleccionada no existe.',
    ];
  }
}
