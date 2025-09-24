<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class UpdateEvaluationPersonRequest extends StoreRequest
{
  public function rules(): array
  {
    // Si comment está presente en el request (sin importar su valor)
    $commentExists = $this->has('comment');

    return [
      'comment' => 'nullable|string|max:500',
      'result' => $commentExists ? [] : ['required', 'numeric', 'min:0']
    ];
  }

  /**
   * Obtiene solo los datos que deben ser actualizados
   */
  public function validated($key = null, $default = null)
  {
    $validated = parent::validated($key, $default);

    // Si comment está presente en el request, removemos result
    if ($this->has('comment')) {
      unset($validated['result']);
    }

    return $validated;
  }
}
