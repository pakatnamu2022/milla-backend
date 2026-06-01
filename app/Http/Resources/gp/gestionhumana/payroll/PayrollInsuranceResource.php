<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollInsuranceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                           => $this->id,
            'worker_id'                    => $this->worker_id,
            'worker'                       => $this->worker?->nombre_completo,
            'period_id'                    => $this->period_id,
            'period'                       => $this->period?->name,
            'business_partner_id'          => $this->business_partner_id,
            'business_partner'             => $this->businessPartner?->name,
            'family_group_number'          => $this->family_group_number,
            'relationship'                 => $this->relationship,
            'doc_type_affiliate'           => $this->doc_type_affiliate,
            'doc_number_affiliate'         => $this->doc_number_affiliate,
            'gender'                       => $this->gender,
            'paternal_surname'             => $this->paternal_surname,
            'maternal_surname'             => $this->maternal_surname,
            'first_name'                   => $this->first_name,
            'second_name'                  => $this->second_name,
            'entry_date'                   => $this->entry_date,
            'birth_date'                   => $this->birth_date,
            'condition'                    => $this->condition,
            'program'                      => $this->program,
            'plan'                         => $this->plan,
            'payment_frequency'            => $this->payment_frequency,
            'type'                         => $this->type,
            'rate_without_tax'             => $this->rate_without_tax,
            'tax'                          => $this->tax,
            'rate_with_tax'                => $this->rate_with_tax,
            'period_from'                  => $this->period_from,
            'period_until'                 => $this->period_until,
            'affiliation_continuity_date'  => $this->affiliation_continuity_date,
            'affiliation_from'             => $this->affiliation_from,
            'affiliation_until'            => $this->affiliation_until,
            'status'                       => $this->status,
            'created_at'                   => $this->created_at,
            'updated_at'                   => $this->updated_at,
        ];
    }
}