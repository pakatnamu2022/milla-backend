<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\maestroGeneral\TaxClassTypes;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\gestionsistema\District;
use Illuminate\Database\Seeder;

class BusinessPartnersTestSeeder extends Seeder
{
  public function run(): void
  {
    // Obtener datos necesarios
    $company = Company::first();
    $district = District::first();
    $taxClassType = TaxClassTypes::first();

    // Obtener masters necesarios
    $origin = ApMasters::where('type', 'ORIGIN')->first();
    $typePerson = ApMasters::where('type', 'TYPE_PERSON')->first();
    $documentType = ApMasters::where('type', 'TYPE_DOCUMENT')->first();
    $personSegment = ApMasters::where('type', 'PERSON_SEGMENT')->first();
    $activityEconomic = ApMasters::where('type', 'ACTIVITY_ECONOMIC')->first();
    $gender = ApMasters::where('type', 'GENDER')->first();
    $typeRoad = ApMasters::where('type', 'TYPE_ROAD')->first();

    if (!$company || !$district || !$taxClassType) {
      $this->command->error('❌ Faltan datos base (Company, District, TaxClassTypes). No se pueden crear clientes de prueba.');
      return;
    }

    if (!$origin || !$typePerson || !$documentType || !$personSegment || !$activityEconomic) {
      $this->command->error('❌ Faltan masters de ApMasters necesarios.');
      return;
    }

    // Crear TYPE_ROAD si no existe
    if (!$typeRoad) {
      $typeRoad = ApMasters::create([
        'description' => 'AVENIDA',
        'type' => 'TYPE_ROAD',
      ]);
    }

    $clients = [
      [
        'first_name' => 'JUAN',
        'paternal_surname' => 'PEREZ',
        'maternal_surname' => 'GARCIA',
        'full_name' => 'JUAN PEREZ GARCIA',
        'num_doc' => '12345678',
        'nationality' => 'NACIONAL',
        'direction' => 'AV. LARCO 123',
        'email' => 'juan.perez@example.com',
        'phone' => '987654321',
        'company_id' => $company->id,
        'district_id' => $district->id,
        'tax_class_type_id' => $taxClassType->id,
        'origin_id' => $origin->id ?? null,
        'type_person_id' => $typePerson->id ?? null,
        'document_type_id' => $documentType->id ?? null,
        'person_segment_id' => $personSegment->id,
        'activity_economic_id' => $activityEconomic->id,
        'gender_id' => $gender->id ?? null,
        'type_road_id' => $typeRoad->id,
      ],
      [
        'first_name' => 'MARIA',
        'paternal_surname' => 'LOPEZ',
        'maternal_surname' => 'TORRES',
        'full_name' => 'MARIA LOPEZ TORRES',
        'num_doc' => '87654321',
        'nationality' => 'NACIONAL',
        'direction' => 'JR. BOLIVAR 456',
        'email' => 'maria.lopez@example.com',
        'phone' => '912345678',
        'company_id' => $company->id,
        'district_id' => $district->id,
        'tax_class_type_id' => $taxClassType->id,
        'origin_id' => $origin->id ?? null,
        'type_person_id' => $typePerson->id ?? null,
        'document_type_id' => $documentType->id ?? null,
        'person_segment_id' => $personSegment->id,
        'activity_economic_id' => $activityEconomic->id,
        'gender_id' => $gender->id ?? null,
        'type_road_id' => $typeRoad->id,
      ],
      [
        'first_name' => 'CARLOS',
        'paternal_surname' => 'RODRIGUEZ',
        'maternal_surname' => 'SILVA',
        'full_name' => 'CARLOS RODRIGUEZ SILVA',
        'num_doc' => '45678912',
        'nationality' => 'NACIONAL',
        'direction' => 'AV. GRAU 789',
        'email' => 'carlos.rodriguez@example.com',
        'phone' => '998765432',
        'company_id' => $company->id,
        'district_id' => $district->id,
        'tax_class_type_id' => $taxClassType->id,
        'origin_id' => $origin->id ?? null,
        'type_person_id' => $typePerson->id ?? null,
        'document_type_id' => $documentType->id ?? null,
        'person_segment_id' => $personSegment->id,
        'activity_economic_id' => $activityEconomic->id,
        'gender_id' => $gender->id ?? null,
        'type_road_id' => $typeRoad->id,
      ],
      [
        'first_name' => 'ANA',
        'paternal_surname' => 'MARTINEZ',
        'maternal_surname' => 'RIOS',
        'full_name' => 'ANA MARTINEZ RIOS',
        'num_doc' => '78912345',
        'nationality' => 'NACIONAL',
        'direction' => 'CA. ZELA 321',
        'email' => 'ana.martinez@example.com',
        'phone' => '956789123',
        'company_id' => $company->id,
        'district_id' => $district->id,
        'tax_class_type_id' => $taxClassType->id,
        'origin_id' => $origin->id ?? null,
        'type_person_id' => $typePerson->id ?? null,
        'document_type_id' => $documentType->id ?? null,
        'person_segment_id' => $personSegment->id,
        'activity_economic_id' => $activityEconomic->id,
        'gender_id' => $gender->id ?? null,
        'type_road_id' => $typeRoad->id,
      ],
      [
        'first_name' => 'LUIS',
        'paternal_surname' => 'FERNANDEZ',
        'maternal_surname' => 'CRUZ',
        'full_name' => 'LUIS FERNANDEZ CRUZ',
        'num_doc' => '32145678',
        'nationality' => 'NACIONAL',
        'direction' => 'AV. PACIFICO 654',
        'email' => 'luis.fernandez@example.com',
        'phone' => '923456789',
        'company_id' => $company->id,
        'district_id' => $district->id,
        'tax_class_type_id' => $taxClassType->id,
        'origin_id' => $origin->id ?? null,
        'type_person_id' => $typePerson->id ?? null,
        'document_type_id' => $documentType->id ?? null,
        'person_segment_id' => $personSegment->id,
        'activity_economic_id' => $activityEconomic->id,
        'gender_id' => $gender->id ?? null,
        'type_road_id' => $typeRoad->id,
      ],
    ];

    $created = 0;
    foreach ($clients as $client) {
      $result = BusinessPartners::firstOrCreate(
        ['num_doc' => $client['num_doc']],
        $client
      );

      if ($result->wasRecentlyCreated) {
        $created++;
      }
    }

    $this->command->info("✅ {$created} clientes de prueba creados exitosamente!");
  }
}
