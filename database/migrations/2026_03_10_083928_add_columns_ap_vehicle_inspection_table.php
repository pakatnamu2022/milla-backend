<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->boolean('oil_change')->default(false)->after('jack_and_lever')->comment('Cambio de Aceite de Filtro');
      $table->boolean('check_level_lights')->default(false)->after('oil_change')->comment('Revisión de Niveles y Luces');
      $table->boolean('general_lubrication')->default(false)->after('check_level_lights')->comment('Engrase General');
      $table->boolean('rotation_inspection_cleaning')->default(false)->after('general_lubrication')->comment('Rotación de Llantas, Revisión y limpieza de Frenos');
      $table->boolean('insp_filter_basic_checks')->default(false)->after('rotation_inspection_cleaning')->comment('Inspección de Filtro de Aire, batería, neumáticos, Suspensión y freno de mano');
      $table->boolean('tire_pressure_inflation_check')->default(false)->after('insp_filter_basic_checks')->comment('Revisión de Presión e Inflado de Llantas');
      $table->boolean('alignment_balancing')->default(false)->after('tire_pressure_inflation_check')->comment('Alineación y Balanceo');
      $table->boolean('pad_replace_disc_resurface')->default(false)->after('alignment_balancing')->comment('Cambio de pastillas de freno y rectificado de discos');
      $table->string('other_work_details')->nullable()->after('pad_replace_disc_resurface')->comment('Detalles de otros trabajos realizados');
      $table->string('customer_requirement')->nullable()->after('other_work_details')->comment('Requerimiento del cliente');
      $table->boolean('explanation_work_performed')->default(false)->after('customer_requirement')->comment('Explicación del trabajo realizado');
      $table->boolean('price_explanation')->default(false)->after('explanation_work_performed')->comment('Explicación del precio');
      $table->boolean('confirm_additional_work')->default(false)->after('price_explanation')->comment('Confirmación de finalización de trabajo adicional');
      $table->boolean('clarification_customer_concerns')->default(false)->after('confirm_additional_work')->comment('Aclaración de inquietudes del cliente');
      $table->boolean('exterior_cleaning')->default(false)->after('clarification_customer_concerns')->comment('Limpieza Exterior');
      $table->boolean('interior_cleaning')->default(false)->after('exterior_cleaning')->comment('Limpieza Interior');
      $table->boolean('keeps_spare_parts')->default(false)->after('interior_cleaning')->comment('Se queda con repuestos');
      $table->boolean('valuable_objects')->default(false)->after('keeps_spare_parts')->comment('Objetos de valor');
      $table->boolean('courtesy_seat_cover')->default(false)->after('valuable_objects')->comment('Cobertor de Asientos');
      $table->boolean('paper_floor')->default(false)->after('seat_cover')->comment('Papel Piso');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->dropColumn([
        'oil_change',
        'check_level_lights',
        'general_lubrication',
        'rotation_inspection_cleaning',
        'insp_filter_basic_checks',
        'tire_pressure_inflation_check',
        'alignment_balancing',
        'pad_replace_disc_resurface',
        'other_work_details',
        'customer_requirement',
        'explanation_work_performed',
        'price_explanation',
        'confirm_additional_work',
        'clarification_customer_concerns',
        'exterior_cleaning',
        'interior_cleaning',
        'keeps_spare_parts',
        'valuable_objects',
        'courtesy_seat_cover',
        'paper_floor'
      ]);
    });
  }
};
