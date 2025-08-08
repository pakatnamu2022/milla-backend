<?php

namespace Database\Seeders;

use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionsistema\Position;
use Illuminate\Database\Seeder;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;

class HierarchicalCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                "name" => "Jefe De Operaciones Y Mantenimiento",
                "positions" => ["289"]
            ],
            [
                "name" => "Logística TP",
                "positions" => ["21"]
            ],
            [
                "name" => "Supervisión de operaciones y mantenimiento",
                "positions" => ["290"]
            ],
            [
                "name" => "Gerente De Negocio TP",
                "positions" => ["22"]
            ],
            [
                "name" => "Asistente Mecánico TP",
                "positions" => ["13"]
            ],
            [
                "name" => "Analista De Contabilidad TP",
                "positions" => ["282"]
            ],
            [
                "name" => "Asistente De Contabilidad TP",
                "positions" => ["283"]
            ],
            [
                "name" => "Asistente De Facturación Y Cobranza",
                "positions" => ["2"]
            ],
            [
                "name" => "Asistente De Operaciones",
                "positions" => ["6"]
            ],
            [
                "name" => "Asistente De Seguimiento Y Monitoreo",
                "positions" => ["5", "304"]
            ],
            [
                "name" => "Asistente En Proyecto Tics",
                "positions" => ["317"]
            ],
            [
                "name" => "Auxiliar De Operaciones Lima",
                "positions" => ["8"]
            ],
            [
                "name" => "Conductor De Tracto Camion",
                "positions" => ["11"]
            ],
            [
                "name" => "Coordinador De Mantenimiento",
                "positions" => ["291"]
            ],
            [
                "name" => "Maestro Mecanico",
                "positions" => ["17"]
            ],
            [
                "name" => "Supervisor Comercial",
                "positions" => ["324"]
            ],
            [
                "name" => "Tecnico Soldador",
                "positions" => ["15"]
            ],
            [
                "name" => "Tecnico Electricista",
                "positions" => ["16", "19"]
            ],
            [
                "name" => "Partner De Gestión Humana",
                "positions" => ["337"]
            ],
            [
                "name" => "Gestor Comercial",
                "positions" => ["3"]
            ],
            [
                "name" => "Gerente General",
                "positions" => ["23"]
            ],
            [
                "name" => "Jefe De Legal",
                "positions" => ["24"]
            ],
            [
                "name" => "Gerente De Gestión Humana",
                "positions" => ["26"]
            ],
            [
                "name" => "Asistente de Atracción De Talento",
                "positions" => ["31"]
            ],
            [
                "name" => "Gerente De Administración Y Finanzas",
                "positions" => ["37"]
            ],
            [
                "name" => "Jefe De Finanzas Y Tesorería",
                "positions" => ["41"]
            ],
            [
                "name" => "Asistente De Contabilidad",
                "positions" => ["47"]
            ],
            [
                "name" => "Jefe De Tic'S",
                "positions" => ["345"]
            ],
            [
                "name" => "Supervisor De Seguridad Patrimonial",
                "positions" => ["254"]
            ],
            [
                "name" => "Operador De Cctv",
                "positions" => ["299"]
            ],
            [
                "name" => "Jefe De Contabilidad Y Caja",
                "positions" => ["335"]
            ],
            [
                "name" => "Asistente De Remuneraciones Y Compensaciones",
                "positions" => ["336"]
            ],
            [
                "name" => "Analista En  Proyectos De Gestion Humana",
                "positions" => ["342"]
            ],
            [
                "name" => "Analista Legal",
                "positions" => ["351"]
            ],
            [
                "name" => "Caja Ap",
                "positions" => ["54", "102", "118", "306"]
            ],
            [
                "name" => "Gerente General",
                "positions" => ["23"]
            ],
            [
                "name" => "Jefe De Legal",
                "positions" => ["24"]
            ],
            [
                "name" => "Gerente De Gestión Humana",
                "positions" => ["26"]
            ],
            [
                "name" => "Asistente de Atracción De Talento",
                "positions" => ["31"]
            ],
            [
                "name" => "Gerente De Administración Y Finanzas",
                "positions" => ["37"]
            ],
            [
                "name" => "Jefe De Finanzas Y Tesorería",
                "positions" => ["41"]
            ],
            [
                "name" => "Asistente De Contabilidad",
                "positions" => ["47"]
            ],
            [
                "name" => "Analista De Remuneraciones Y Compensaciones",
                "positions" => ["53"]
            ],
            [
                "name" => "Auditor Interno",
                "positions" => ["220"]
            ],
            [
                "name" => "Operador De Cctv",
                "positions" => ["299"]
            ],
            [
                "name" => "Jefe De Contabilidad Y Caja",
                "positions" => ["335"]
            ],
            [
                "name" => "Asistente De Remuneraciones Y Compensaciones",
                "positions" => ["336"]
            ],
            [
                "name" => "Analista En  Proyectos De Gestion Humana",
                "positions" => ["342"]
            ],
            [
                "name" => "Analista Legal",
                "positions" => ["351"]
            ],
            [
                "name" => "Caja Ap",
                "positions" => ["54", "102", "118", "306"]
            ],
            [
                "name" => "Jefe De Almacen",
                "positions" => ["56", "86", "248"]
            ],
            [
                "name" => "Asistente De Almacén Ap",
                "positions" => ["57", "87", "251", "129"]
            ],
            [
                "name" => "Coordinador De Ventas",
                "positions" => ["59", "81", "104", "110", "121"]
            ],
            [
                "name" => "Asesor Comercial",
                "positions" => ["60", "80", "103", "108", "111", "119", "332", "328"]
            ],
            [
                "name" => "Jefe De Ventas Ap",
                "positions" => ["61", "82", "107", "114", "124", "331", "327"]
            ],
            [
                "name" => "Asesor De Repuestos",
                "positions" => ["62", "72", "88", "130", "349"]
            ],
            [
                "name" => "Asesor De Servicios",
                "positions" => ["63", "73", "89", "131"]
            ],
            [
                "name" => "Asistente Mecánico Ap",
                "positions" => ["64", "74", "91", "134"]
            ],
            [
                "name" => "Auxiliar De Lavado Y Limpieza",
                "positions" => ["65", "75", "92", "135"]
            ],
            [
                "name" => "Auxiliar De Limpieza Y Conserjeria",
                "positions" => ["66", "76", "93", "113", "116", "334", "330", "150", "160", "171", "195", "204", "214", "253"]
            ],
            [
                "name" => "Coordinador De Taller",
                "positions" => ["68", "78", "98", "140"]
            ],
            [
                "name" => "Jefe De Taller",
                "positions" => ["69", "99", "143", "246"]
            ],
            [
                "name" => "Tecnico Mecanico Ap",
                "positions" => ["70", "79", "100", "144"]
            ],
            [
                "name" => "Agente De Seguridad",
                "positions" => ["258", "259", "260", "268", "269", "270", "350", "333", "329", "261", "262", "264", "266", "267", "271", "257"]
            ],
            [
                "name" => "Codificador De Repuestos",
                "positions" => ["77", "97", "139"]
            ],
            [
                "name" => "Gestor De Inmatriculacion",
                "positions" => ["106", "123", "298"]
            ],
            [
                "name" => "Analista De Contabilidad AP",
                "positions" => ["301", "302"]
            ],
            [
                "name" => "Asistente De Contabilidad AP",
                "positions" => ["275", "300"]
            ],
            [
                "name" => "Asistente Administrativo Ap",
                "positions" => ["85", "117"]
            ],
            [
                "name" => "Asistente De Post Venta",
                "positions" => ["90", "133"]
            ],
            [
                "name" => "Asistente De Vehiculos (pdi)",
                "positions" => ["132", "249"]
            ],
            [
                "name" => "Asistente De Marketing",
                "positions" => ["120"]
            ],
            [
                "name" => "Ejecutivo De Trade Marketing",
                "positions" => ["125"]
            ],
            [
                "name" => "Gerente De Negocio Ap",
                "positions" => ["126"]
            ],
            [
                "name" => "Jefe De Marketing",
                "positions" => ["127"]
            ],
            [
                "name" => "Coordinador De Post Venta - Almacen",
                "positions" => ["128"]
            ],
            [
                "name" => "Coordinador De Post Venta",
                "positions" => ["141"]
            ],
            [
                "name" => "Gerente De Post Venta",
                "positions" => ["142"]
            ],
            [
                "name" => "Community Manager",
                "positions" => ["218"]
            ],
            [
                "name" => "Diseñador Grafico",
                "positions" => ["219"]
            ],
            [
                "name" => "Analista de Negocio",
                "positions" => ["256"]
            ],
            [
                "name" => "Analista En Proyecto Tics",
                "positions" => ["273", "280"]
            ],
            [
                "name" => "Trabajadora Social",
                "positions" => ["286", "287"]
            ],
            [
                "name" => "Jefe De Contabilidad",
                "positions" => ["288"]
            ],
            [
                "name" => "Partner De Gestión Humana",
                "positions" => ["341"]
            ],
            [
                "name" => "Jefe De Repuestos",
                "positions" => ["344"]
            ],
            [
                "name" => "Analista Comercial Dp",
                "positions" => ["303"]
            ],
            [
                "name" => "Analista De Contabilidad DP",
                "positions" => ["277"]
            ],
            [
                "name" => "Asistente De Contabilidad Dp",
                "positions" => ["149", "157", "172", "181", "194", "206", "278"]
            ],
            [
                "name" => "Asistente Administrativo Dp",
                "positions" => ["145", "184", "207", "234", "305"]
            ],
            [
                "name" => "Asistente de Almacén DP",
                "positions" => ["233", "309"]
            ],
            [
                "name" => "Asistente De Reparto",
                "positions" => ["151", "162", "174", "185", "196", "209", "215"]
            ],
            [
                "name" => "Asistente De Reparto - Montacarguista",
                "positions" => ["238", "239", "240", "241", "242", "243"]
            ],
            [
                "name" => "Caja Dp",
                "positions" => ["148", "158", "170", "183", "192", "205"]
            ],
            [
                "name" => "Caja General",
                "positions" => ["146", "161", "173", "182", "193", "208"]
            ],
            [
                "name" => "Conductor I",
                "positions" => ["153", "165", "176", "187", "198", "210"]
            ],
            [
                "name" => "Conductor II",
                "positions" => ["164", "188", "199", "211", "346"]
            ],
            [
                "name" => "Ejecutivo Comercial",
                "positions" => ["156", "167", "178", "190", "202", "212", "201", "314", "315", "316", "154"]
            ],
            [
                "name" => "Gerente Comercial Dp",
                "positions" => ["217"]
            ],
            [
                "name" => "Jefe De Administracion",
                "positions" => ["147", "159", "169", "179", "180", "203", "236", "191"]
            ],
            [
                "name" => "Jefe De Ventas Dp",
                "positions" => ["155", "168", "353"]
            ],
            [
                "name" => "Supervisor De Almacen",
                "positions" => ["152", "163", "175", "186", "197", "231"]
            ],
            [
                "name" => "Ejecutivo Comercial",
                "positions" => null
            ],
            [
                "name" => "Ejecutivo Comercial Industria",
                "positions" => null
            ]
        ];

        HierarchicalCategoryDetail::where("position_id", "<>", 46464646)->delete();
        HierarchicalCategory::where("name", "<>", "sudo")->delete();

        foreach ($data as $item) {

            $categoryFounded = HierarchicalCategory::where('name', $item['name'])->first();

            if (!$categoryFounded) {
                $category = HierarchicalCategory::create([
                    'name' => $item['name'],
                    'description' => 'Category for ' . $item['name']
                ]);
                if (!empty($item['positions']) && is_array($item['positions'])) {
                    $validIds = Position::whereIn('id', $item['positions'])->pluck('id')->toArray();
                    foreach ($validIds as $positionId) {
                        $category->children()->create([
                            'position_id' => $positionId,
                        ]);
                    }

                }
            }
        }
    }
}
