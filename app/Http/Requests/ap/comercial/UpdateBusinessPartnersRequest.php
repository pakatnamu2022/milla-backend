<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessPartnersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'required|string|max:255',
      'paternal_surname' => 'required|string|max:255',
      'maternal_surname' => 'required|string|max:255',
      'birth_date' => 'nullable|date|before:today',
      'nationality' => 'required|string|in:NACIONAL,EXTRANJERO',
      'num_doc' => [
        'required',
        'string',
        'max:20',
        Rule::unique('business_partners', 'num_doc')
          ->where('company_id', $this->input('company_id'))
          ->ignore($this->route('businessPartner'))
          ->whereNull('deleted_at'),
      ],
      'spouse_num_doc' => 'nullable|string|max:20',
      'spouse_name' => 'nullable|string|max:255',
      'spouse_paternal_surname' => 'nullable|string|max:255',
      'spouse_maternal_surname' => 'nullable|string|max:255',
      'direction' => 'nullable|string|max:500',
      'legal_representative_num_doc' => 'nullable|string|max:20',
      'legal_representative_name' => 'nullable|string|max:255',
      'legal_representative_paternal_surname' => 'nullable|string|max:255',
      'legal_representative_maternal_surname' => 'nullable|string|max:255',
      'email' => 'nullable|email:rfc,dns|max:255',
      'secondary_email' => 'nullable|email:rfc,dns|max:255',
      'tertiary_email' => 'nullable|email:rfc,dns|max:255',
      'phone' => 'nullable|string|max:20',
      'secondary_phone' => 'nullable|string|max:20',
      'tertiary_phone' => 'nullable|string|max:20',
      'secondary_phone_contact_name' => 'nullable|string|max:255',
      'tertiary_phone_contact_name' => 'nullable|string|max:255',
      'driving_license' => 'nullable|string|max:50',
      'driving_license_place' => 'nullable|string|max:255',
      'driving_license_issue_date' => 'nullable|date|before_or_equal:today',
      'driving_license_expiration_date' => 'nullable|date|after:driving_license_issue_date',
      'origin_id' => 'required|integer|exists:ap_commercial_masters,id',
      'driving_license_type' => 'nullable|integer|exists:ap_commercial_masters,id',
      'tax_class_type_id' => 'required|integer|exists:tax_class_types,id',
      'type_road_id' => 'nullable|integer|exists:ap_commercial_masters,id',
      'type_person_id' => 'required|integer|exists:ap_commercial_masters,id',
      'district_id' => 'required|integer|exists:district,id',
      'document_type_id' => 'required|integer|exists:ap_commercial_masters,id',
      'person_segment_id' => 'required|integer|exists:ap_commercial_masters,id',
      'marital_status_id' => 'required|integer|exists:ap_commercial_masters,id',
      'gender_id' => 'required|integer|exists:ap_commercial_masters,id',
      'activity_economic_id' => 'required|integer|exists:ap_commercial_masters,id',
      'company_id' => 'required|integer|exists:companies,id',
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'El nombre es obligatorio.',
      'name.string' => 'El nombre debe ser una cadena de texto.',
      'name.max' => 'El nombre no debe exceder los 255 caracteres.',

      'paternal_surname.required' => 'El apellido paterno es obligatorio.',
      'paternal_surname.string' => 'El apellido paterno debe ser una cadena de texto.',
      'paternal_surname.max' => 'El apellido paterno no debe exceder los 255 caracteres.',

      'maternal_surname.required' => 'El apellido materno es obligatorio.',
      'maternal_surname.string' => 'El apellido materno debe ser una cadena de texto.',
      'maternal_surname.max' => 'El apellido materno no debe exceder los 255 caracteres.',

      'birth_date.date' => 'La fecha de nacimiento debe ser una fecha válida.',
      'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',

      'nationality.required' => 'La nacionalidad es obligatoria.',
      'nationality.in' => 'La nacionalidad debe ser NACIONAL o EXTRANJERO.',

      'num_doc.required' => 'El número de documento es obligatorio.',
      'num_doc.string' => 'El número de documento debe ser una cadena de texto.',
      'num_doc.max' => 'El número de documento no debe exceder los 20 caracteres.',
      'num_doc.unique' => 'El número de documento ya está registrado para esta empresa.',

      'email.email' => 'El email debe tener un formato válido.',
      'secondary_email.email' => 'El email secundario debe tener un formato válido.',
      'tertiary_email.email' => 'El email terciario debe tener un formato válido.',

      'driving_license_issue_date.date' => 'La fecha de emisión de la licencia debe ser una fecha válida.',
      'driving_license_issue_date.before_or_equal' => 'La fecha de emisión de la licencia no puede ser futura.',
      'driving_license_expiration_date.date' => 'La fecha de vencimiento de la licencia debe ser una fecha válida.',
      'driving_license_expiration_date.after' => 'La fecha de vencimiento debe ser posterior a la fecha de emisión.',

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

      'marital_status_id.required' => 'El estado civil es obligatorio.',
      'marital_status_id.exists' => 'El estado civil seleccionado no existe.',

      'gender_id.required' => 'El género es obligatorio.',
      'gender_id.exists' => 'El género seleccionado no existe.',

      'activity_economic_id.required' => 'La actividad económica es obligatoria.',
      'activity_economic_id.exists' => 'La actividad económica seleccionada no existe.',

      'company_id.required' => 'La empresa es obligatoria.',
      'company_id.exists' => 'La empresa seleccionada no existe.',
    ];
  }
}
