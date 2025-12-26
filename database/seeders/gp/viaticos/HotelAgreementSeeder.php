<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\HotelAgreement;
use Illuminate\Database\Seeder;

class HotelAgreementSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $hotels = [
      // Lima - 3 hoteles
      [
        'city' => 'Lima - San Miguel',
        'name' => 'SM Hotel &Business',
        'corporate_rate' => 150.00,
        'features' => 'Desayuno americano, Estacionamiento (sujeto a disponibilidad)',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'ventas@smhotel.pe',
        'phone' => '997073555',
        'address' => 'Av. De los Patriotas 623 Urb. Maranga, San Miguel',
        'website' => 'https://smhotel.pe/',
        'active' => true,
      ],
      [
        'city' => 'Lima - San Isidro',
        'name' => 'Hotel El Angolo',
        'corporate_rate' => 180.00,
        'features' => 'Desayuno americano, Estacionamiento',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'reservas@hoteleselangolo.com',
        'phone' => '(01) 6521379',
        'address' => 'Oropéndolas 341, San Isidro',
        'website' => 'https://www.hoteleselangolo.com/lima_san_isidro/',
        'active' => true,
      ],
      [
        'city' => 'Lima - Miraflores',
        'name' => 'Hotel Ibis Budget',
        'corporate_rate' => 210.00,
        'features' => 'Desayuno buffet, Estacionamiento (sujeto a disponibilidad)',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'HA8F5-RE@accor.com',
        'phone' => '(01) 7301700',
        'address' => 'Calle Alcanfores 677, Miraflores',
        'website' => 'http://ibis-budget-miraflores.limaperuhotels.net/es/',
        'active' => true,
      ],

      // Piura - 2 hoteles
      [
        'city' => 'Piura',
        'name' => 'Reycer Hoteles',
        'corporate_rate' => 95.00,
        'features' => 'Desayuno',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => false,
        'email' => '',
        'phone' => '(073) 254092 / 938263422',
        'address' => 'Jr. Ica 553',
        'website' => 'https://www.instagram.com/reycer_hoteles?igsh=MXNuYno0eHIwcm9wOA==',
        'active' => true,
      ],
      [
        'city' => 'Piura',
        'name' => 'Lyz Business Hotel',
        'corporate_rate' => 168.00,
        'features' => 'Desayuno, Cochera',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => '',
        'phone' => '949175229',
        'address' => 'Av. Vice 230, Piura',
        'website' => 'www.lyhotel.com',
        'active' => true,
      ],

      // Chiclayo - 2 hoteles
      [
        'city' => 'Chiclayo',
        'name' => 'Hotel Casa de Luna',
        'corporate_rate' => 120.00,
        'features' => 'Desayuno',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => false,
        'email' => 'reservas@hotel-casadelaluna.com',
        'phone' => '933965994',
        'address' => 'Bernando Alcedo 250 Urb. Patazca',
        'website' => 'https://www.facebook.com/profile.php?id=100083087354622&mibextid=ZbWKwL',
        'active' => true,
      ],
      [
        'city' => 'Chiclayo',
        'name' => 'Hotel Descanso del Inca',
        'corporate_rate' => 130.00,
        'features' => 'Desayuno americano',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => false,
        'email' => 'descansodelinca@gmail.com',
        'phone' => '947913324',
        'address' => 'Manuel María Izaga 836',
        'website' => 'http://hoteldescansodelinca.com/',
        'active' => true,
      ],

      // Cajamarca - 2 hoteles
      [
        'city' => 'Cajamarca',
        'name' => 'El Cabildo Hostal',
        'corporate_rate' => 110.00,
        'features' => 'Desayuno variado, Estacionamiento con costo adicional',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => false,
        'email' => 'elcabildoh@gmail.com',
        'phone' => '(076) 367025',
        'address' => 'Jr. Junín 1062',
        'website' => 'https://www.facebook.com/ElCabildoH/?locale=es_LA',
        'active' => true,
      ],
      [
        'city' => 'Cajamarca',
        'name' => 'Numay',
        'corporate_rate' => 50.00,
        'features' => 'Desayuno adicional S/15, Estacionamiento con costo adicional',
        'includes_breakfast' => false,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => false,
        'email' => 'reservas@numayhotel.com',
        'phone' => '961362884',
        'address' => 'Jr. Miguel Iglesias 792 Prolongación Revilla Pérez',
        'website' => 'www.facebook.com/numaydhotel',
        'active' => true,
      ],

      // Jaén - 3 hoteles
      [
        'city' => 'Jaén',
        'name' => 'Hotel El Bosque',
        'corporate_rate' => 140.00,
        'features' => 'Desayuno, Estacionamiento',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'reservas@hotelelbosquejaen.com',
        'phone' => '(076) 269105',
        'address' => 'Av. Manuel Antonio Mesones Muro 632',
        'website' => 'https://hotelelbosquejaen.com/',
        'active' => true,
      ],
      [
        'city' => 'Jaén',
        'name' => 'Business Class Hotel',
        'corporate_rate' => 90.00,
        'features' => 'Desayuno, Estacionamiento (según disponibilidad)',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'reservas@bcprimshotel.com',
        'phone' => '(076) 438967',
        'address' => 'Calle San Martín 1261',
        'website' => 'http://www.bcprimshotel.com/',
        'active' => true,
      ],
      [
        'city' => 'Jaén',
        'name' => 'Prim\'s Hotel',
        'corporate_rate' => 70.00,
        'features' => 'Desayuno, Estacionamiento',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => '',
        'phone' => '979881568',
        'address' => 'Calle Diego Palomino 1341',
        'website' => 'https://www.primshotel.com/',
        'active' => true,
      ],
    ];

    foreach ($hotels as $hotel) {
      HotelAgreement::firstOrCreate(
        [
          'city' => $hotel['city'],
          'name' => $hotel['name'],
        ],
        $hotel
      );
    }
  }
}
