<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\IndexDriverStatusLogRequest;
use App\Http\Services\tp\comercial\DriverStatusLogService;
use Throwable;

class DriverStatusLogController extends Controller
{
    protected $service;

    public function __construct(DriverStatusLogService $service)
    {
        $this->service = $service;
    }

    public function index(IndexDriverStatusLogRequest $request)
    {
        try {
            return $this->service->index($request);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function byDriver($id)
    {
        try {
            return $this->service->byDriver($id);
        } catch(Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}