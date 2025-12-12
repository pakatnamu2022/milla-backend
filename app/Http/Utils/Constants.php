<?php

namespace App\Http\Utils;

class Constants
{
  public const int DEFAULT_PER_PAGE = 10;
  public const int MAX_ALL_PER_QUERY = 50;

  public const int COMPANY_TP = 1;
  public const int COMPANY_DP = 2;
  public const int COMPANY_AP = 3;
  public const int COMPANY_GP = 4;

  public const int WORKER_ACTIVE = 22;

  public const int TYPE_PERSON_NATURAL_ID = 704; // Persona Natural

  public const int TYPE_PERSON_JURIDICA_ID = 705; // Persona Juridica

  public const int TYPE_DOCUMENT_RUC_ID = 810; // RUC

  public const int TYPE_DOCUMENT_DNI_ID = 809; // DNI

  public const int TYPE_OPERATION_COMERCIAL_ID = 794; // Tipo de Operación COMERCIAL
  public const int TYPE_OPERATION_POSTVENTA_ID = 804; // Tipo de Operación POSTVENTA

  public const int VAT_TAX = 18; // IGV
  public const int TICS_AREA_ID = 11;

  public const int SALE_COORDINATOR_CATEGORY_ID = 14; // Categoría Coordinador de Ventas
}
