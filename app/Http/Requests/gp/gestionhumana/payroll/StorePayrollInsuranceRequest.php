<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StorePayrollInsuranceRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id'                   => ['required', 'integer', 'exists:rrhh_persona,id'],
            'period_id'                   => ['required', 'integer', 'exists:gh_payroll_periods,id'],
            'business_partner_id'         => ['required', 'integer', 'exists:business_partners,id'],
            'family_group_number'         => ['nullable', 'string', 'max:50'],
            'relationship'                => ['required', 'string', 'max:50'],
            'doc_type_affiliate'          => ['nullable', 'string', 'max:50'],
            'doc_number_affiliate'        => ['nullable', 'string', 'max:20'],
            'gender'                      => ['nullable', 'string', 'max:20'],
            'paternal_surname'            => ['nullable', 'string', 'max:100'],
            'maternal_surname'            => ['nullable', 'string', 'max:100'],
            'first_name'                  => ['nullable', 'string', 'max:100'],
            'second_name'                 => ['nullable', 'string', 'max:100'],
            'entry_date'                  => ['nullable', 'date'],
            'birth_date'                  => ['nullable', 'date'],
            'condition'                   => ['nullable', 'string', 'max:100'],
            'program'                     => ['nullable', 'string', 'max:150'],
            'plan'                        => ['nullable', 'string', 'max:150'],
            'payment_frequency'           => ['nullable', 'string', 'max:50'],
            'type'                        => ['nullable', 'string', 'max:100'],
            'rate_without_tax'            => ['nullable', 'numeric', 'min:0'],
            'tax'                         => ['nullable', 'numeric', 'min:0'],
            'rate_with_tax'               => ['nullable', 'numeric', 'min:0'],
            'period_from'                 => ['nullable', 'date'],
            'period_until'                => ['nullable', 'date', 'after_or_equal:period_from'],
            'affiliation_continuity_date' => ['nullable', 'date'],
            'affiliation_from'            => ['nullable', 'date'],
            'affiliation_until'           => ['nullable', 'date', 'after_or_equal:affiliation_from'],
            'status'                      => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'worker_id'                   => 'trabajador',
            'period_id'                   => 'periodo',
            'business_partner_id'         => 'empresa',
            'family_group_number'         => 'nro grupo familiar',
            'relationship'                => 'parentesco',
            'doc_type_affiliate'          => 'tipo doc afiliado',
            'doc_number_affiliate'        => 'núm doc afiliado',
            'gender'                      => 'sexo',
            'paternal_surname'            => 'apellido paterno',
            'maternal_surname'            => 'apellido materno',
            'first_name'                  => 'primer nombre',
            'second_name'                 => 'segundo nombre',
            'entry_date'                  => 'fecha de ingreso',
            'birth_date'                  => 'fecha de nacimiento',
            'condition'                   => 'condición',
            'program'                     => 'programa',
            'plan'                        => 'plan',
            'payment_frequency'           => 'frecuencia de pago',
            'type'                        => 'tipo',
            'rate_without_tax'            => 'tarifa sin IGV',
            'tax'                         => 'IGV',
            'rate_with_tax'               => 'tarifa con IGV',
            'period_from'                 => 'período desde',
            'period_until'                => 'período hasta',
            'affiliation_continuity_date' => 'fecha continuidad afiliación',
            'affiliation_from'            => 'fecha de afiliación desde',
            'affiliation_until'           => 'fecha de afiliación hasta',
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.required'          => 'El :attribute es obligatorio.',
            'worker_id.exists'            => 'El :attribute seleccionado no existe.',
            'period_id.required'          => 'El :attribute es obligatorio.',
            'period_id.exists'            => 'El :attribute seleccionado no existe.',
            'business_partner_id.required' => 'La :attribute es obligatoria.',
            'business_partner_id.exists'  => 'La :attribute seleccionada no existe.',
            'relationship.required'       => 'El :attribute es obligatorio.',
            'rate_without_tax.min'        => 'La :attribute no puede ser negativa.',
            'tax.min'                     => 'El :attribute no puede ser negativo.',
            'rate_with_tax.min'           => 'La :attribute no puede ser negativa.',
            'period_until.after_or_equal' => 'El :attribute debe ser igual o posterior al período desde.',
            'affiliation_until.after_or_equal' => 'La :attribute debe ser igual o posterior a la fecha de afiliación desde.',
        ];
    }
}