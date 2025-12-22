<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\IndexRequest;

class IndexHotelAgreementRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      "all" => "sometimes|string|in:true,false",
    ];
  }
}
