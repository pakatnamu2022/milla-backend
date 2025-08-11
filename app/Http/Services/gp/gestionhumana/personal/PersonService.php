<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPeriodResource;
use App\Http\Resources\gp\gestionhumana\personal\PersonResource;
use App\Http\Resources\PersonBirthdayResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPeriod;
use App\Models\gp\gestionsistema\Person;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            Person::where('status_deleted', 1),
            $request,
            Person::filters,
            Person::sorts,
            PersonResource::class,
        );
    }

    public function listBirthdays(Request $request)
    {
        $query = Person::query()
            ->working()
            ->select('*')
            ->selectRaw("
            DATEDIFF(
                DATE_ADD(
                    fecha_nacimiento,
                    INTERVAL (YEAR(CURDATE()) - YEAR(fecha_nacimiento)) +
                    (DATE_FORMAT(fecha_nacimiento, '%m-%d') < DATE_FORMAT(CURDATE(), '%m-%d')) YEAR
                ),
                CURDATE()
            ) as days_to_birthday
        ")
            ->orderBy('days_to_birthday');

        return $this->getFilteredResults(
            $query,
            $request,
            Person::filters,
            Person::sorts,
            PersonBirthdayResource::class
        );
    }


    public function find($id)
    {
        $evaluationCompetence = EvaluationPeriod::where('id', $id)->first();
        if (!$evaluationCompetence) {
            throw new Exception('Periodo no encontrado');
        }
        return $evaluationCompetence;
    }

    public function store(array $data)
    {
        $evaluationMetric = EvaluationPeriod::create($data);
        return new EvaluationPeriodResource($evaluationMetric);
    }

    public function show($id)
    {
        return new EvaluationPeriodResource($this->find($id));
    }

    public function update($data)
    {
        $evaluationCompetence = $this->find($data['id']);
        $evaluationCompetence->update($data);
        return new EvaluationPeriodResource($evaluationCompetence);
    }

    public function destroy($id)
    {
        $evaluationCompetence = $this->find($id);
        DB::transaction(function () use ($evaluationCompetence) {
            $evaluationCompetence->delete();
        });
        return response()->json(['message' => 'Periodo eliminado correctamente']);
    }
}
