<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessPartnersResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'first_name' => $this->first_name,
      'middle_name' => $this->middle_name,
      'paternal_surname' => $this->paternal_surname,
      'maternal_surname' => $this->maternal_surname,
      'full_name' => $this->full_name,
      'birth_date' => $this->birth_date?->format('Y-m-d'),
      'nationality' => $this->nationality,
      'num_doc' => $this->num_doc,

      // Datos del cónyuge
      'spouse_num_doc' => $this->spouse_num_doc,
      'spouse_full_name' => $this->spouse_full_name,

      // Dirección
      'direction' => $this->direction,

      // Representante legal
      'legal_representative_num_doc' => $this->legal_representative_num_doc,
      'legal_representative_name' => $this->legal_representative_name,
      'legal_representative_paternal_surname' => $this->legal_representative_paternal_surname,
      'legal_representative_maternal_surname' => $this->legal_representative_maternal_surname,
      'legal_representative_full_name' => $this->legal_representative_full_name,

      // Contactos
      'email' => $this->email,
      'secondary_email' => $this->secondary_email,
      'phone' => $this->phone,
      'secondary_phone' => $this->secondary_phone,
      'secondary_phone_contact_name' => $this->secondary_phone_contact_name,

      // Licencia de conducir
      'driver_num_doc' => $this->driver_num_doc,
      'driver_full_name' => $this->driver_full_name,
      'driving_license' => $this->driving_license,
      'driving_license_expiration_date' => $this->driving_license_expiration_date?->format('Y-m-d'),
      'status_license' => $this->status_license,
      'restriction' => $this->restriction,
      'company_status' => $this->company_status ?? '-',
      'company_condition' => $this->company_condition ?? '-',

      // IDs de relaciones
      'origin_id' => $this->origin_id,
      'driving_license_category' => $this->driving_license_category,
      'tax_class_type_id' => $this->tax_class_type_id,
      'supplier_tax_class_id' => $this->supplier_tax_class_id,
      'type_person_id' => $this->type_person_id,
      'district_id' => $this->district_id,
      'document_type_id' => $this->document_type_id,
      'person_segment_id' => $this->person_segment_id,
      'marital_status_id' => $this->marital_status_id,
      'gender_id' => $this->gender_id,
      'activity_economic_id' => $this->activity_economic_id,
      'company_id' => $this->company_id,

      // Relaciones cargadas
      'origin' => $this->origin?->description,
      'tax_class_type' => $this->taxClassType?->description,
      'type_road' => $this->typeRoad?->description,
      'type_person' => $this->typePerson?->description,
      'district' => $this->district->name . ' - ' . $this->district->province->name . ' - ' . $this->district->province->department->name,
      'document_type' => $this->documentType?->description,
      'person_segment' => $this->personSegment?->description,
      'marital_status' => $this->maritalStatus?->description,
      'gender' => $this->gender?->description,
      'activity_economic' => $this->activityEconomic?->description,
      'company' => $this->company->name,
      'type' => $this->type,
    ];
  }
}
