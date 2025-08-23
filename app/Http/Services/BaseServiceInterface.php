<?php

namespace App\Http\Services;

use Illuminate\Http\Request;

interface BaseServiceInterface
{
  public function list(Request $request);

  public function store(Mixed $data);

  public function find(int $id);

  public function update(Mixed $data);

  public function destroy(int $id);

}
