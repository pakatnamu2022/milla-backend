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
        'city' => 'Lima',
        'name' => 'Hotel Costa del Sol Wyndham Lima',
        'corporate_rate' => 180.00,
        'features' => 'WiFi, Gimnasio, Restaurante, Desayuno Buffet',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'reservas@costadelsol.pe',
        'phone' => '(01) 711-2000',
        'address' => 'Av. Salaverry 3060, San Isidro, Lima',
        'website' => 'https://www.costadelsolperu.com',
        'active' => true,
      ],
      [
        'city' => 'Lima',
        'name' => 'Britania Miraflores Hotel',
        'corporate_rate' => 150.00,
        'features' => 'WiFi, Centro de Negocios, Desayuno Continental',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => false,
        'email' => 'reservas@britaniahotel.com',
        'phone' => '(01) 617-6060',
        'address' => 'Av. Benavides 415, Miraflores, Lima',
        'website' => 'https://www.britaniahotel.com',
        'active' => true,
      ],
      [
        'city' => 'Lima',
        'name' => 'NM Lima Hotel',
        'corporate_rate' => 120.00,
        'features' => 'WiFi, Room Service, Desayuno Americano',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'reservas@nmlimahotel.com',
        'phone' => '(01) 614-4040',
        'address' => 'Av. Arequipa 4515, Miraflores, Lima',
        'website' => 'https://www.nmlimahotel.com',
        'active' => true,
      ],

      // Arequipa - 2 hoteles
      [
        'city' => 'Arequipa',
        'name' => 'Casa Andina Premium Arequipa',
        'corporate_rate' => 160.00,
        'features' => 'WiFi, Spa, Restaurante Gourmet, Desayuno Buffet',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'reservas@casa-andina.com',
        'phone' => '(054) 226-070',
        'address' => 'Calle Ugarte 403, Cercado, Arequipa',
        'website' => 'https://www.casa-andina.com',
        'active' => true,
      ],
      [
        'city' => 'Arequipa',
        'name' => 'Hotel El Cabildo',
        'corporate_rate' => 110.00,
        'features' => 'WiFi, Terraza, Desayuno Continental',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => false,
        'email' => 'info@hotelcabildo.com',
        'phone' => '(054) 234-567',
        'address' => 'Calle San Francisco 309, Arequipa',
        'website' => 'https://www.hotelcabildo.com',
        'active' => true,
      ],

      // Cusco - 2 hoteles
      [
        'city' => 'Cusco',
        'name' => 'Hotel Novotel Cusco',
        'corporate_rate' => 180.00,
        'features' => 'WiFi, Oxígeno en habitaciones, Restaurante, Desayuno Buffet',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'reservas@novotelcusco.com',
        'phone' => '(084) 581-030',
        'address' => 'Av. El Sol 954, Cusco',
        'website' => 'https://www.novotelcusco.com',
        'active' => true,
      ],
      [
        'city' => 'Cusco',
        'name' => 'Hotel Tierra Viva Cusco Centro',
        'corporate_rate' => 130.00,
        'features' => 'WiFi, Terraza, Desayuno Buffet',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => false,
        'email' => 'reservas@tierraviva.com',
        'phone' => '(084) 581-620',
        'address' => 'Calle Suecia 345, Cusco',
        'website' => 'https://www.tierravivahoteles.com',
        'active' => true,
      ],

      // Trujillo - 2 hoteles
      [
        'city' => 'Trujillo',
        'name' => 'Casa Andina Select Trujillo',
        'corporate_rate' => 140.00,
        'features' => 'WiFi, Piscina, Restaurante, Desayuno Buffet',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'reservas@casa-andina.com',
        'phone' => '(044) 205-050',
        'address' => 'Av. El Golf 591, Urb. El Golf, Trujillo',
        'website' => 'https://www.casa-andina.com',
        'active' => true,
      ],
      [
        'city' => 'Trujillo',
        'name' => 'Hotel Costa del Sol Trujillo',
        'corporate_rate' => 130.00,
        'features' => 'WiFi, Centro de Negocios, Desayuno Continental',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'trujillo@costadelsol.pe',
        'phone' => '(044) 484-150',
        'address' => 'Av. América Oeste 2050, Urb. Natasha Alta, Trujillo',
        'website' => 'https://www.costadelsolperu.com',
        'active' => true,
      ],

      // Piura - 1 hotel
      [
        'city' => 'Piura',
        'name' => 'Costa del Sol Wyndham Piura',
        'corporate_rate' => 135.00,
        'features' => 'WiFi, Piscina, Restaurante, Desayuno Buffet',
        'includes_breakfast' => true,
        'includes_lunch' => false,
        'includes_dinner' => false,
        'includes_parking' => true,
        'email' => 'piura@costadelsol.pe',
        'phone' => '(073) 302-864',
        'address' => 'Calle Libertad 875, Piura',
        'website' => 'https://www.costadelsolperu.com',
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
