<?php

namespace App\Http\Services;

use App\Http\Resources\ManualResource;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\gp\gestionsistema\View;
use App\Models\Manual;
use Illuminate\Http\Request;

class ManualService extends BaseService implements BaseServiceInterface
{
    private const S3_PATH = 'manuales/';

    public function __construct(private DigitalFileService $digitalFileService) {}

    public function list(Request $request)
    {
        return $this->getFilteredResults(
            Manual::class,
            $request,
            Manual::filters,
            Manual::sorts,
            ManualResource::class,
        );
    }

    public function find(int $id): Manual
    {
        return Manual::findOrFail($id);
    }

    public function show(int $id): ManualResource
    {
        return new ManualResource($this->find($id)->load('digitalFile'));
    }

    public function store(mixed $data): ManualResource
    {
        $file = $data['file'];
        unset($data['file']);

        $vista       = View::with('company')->findOrFail($data['vista_id']);
        $companySlug = strtolower($vista->company->abbreviation);
        $moduleSlug  = $vista->slug;

        $path        = self::S3_PATH . $companySlug . '/' . $moduleSlug . '/';
        $digitalFile = $this->digitalFileService->store($file, $path, 'public', 'manuals')->resource;

        $manual = Manual::create(array_merge($data, [
            'digital_file_id' => $digitalFile->id,
            'company_slug'    => $companySlug,
            'module_slug'     => $moduleSlug,
            'order'           => $data['order'] ?? 0,
        ]));

        $digitalFile->update(['id_model' => $manual->id]);

        return new ManualResource($manual->load('digitalFile'));
    }

    public function update(mixed $data): ManualResource
    {
        $manual = $this->find($data['id']);
        unset($data['id']);

        if (isset($data['vista_id'])) {
            $vista                = View::with('company')->findOrFail($data['vista_id']);
            $data['company_slug'] = strtolower($vista->company->abbreviation);
            $data['module_slug']  = $vista->slug;
        }

        if (isset($data['file'])) {
            $this->digitalFileService->destroy($manual->digital_file_id);

            $companySlug = $data['company_slug'] ?? $manual->company_slug;
            $moduleSlug  = $data['module_slug']  ?? $manual->module_slug;
            $path        = self::S3_PATH . $companySlug . '/' . $moduleSlug . '/';

            $digitalFile              = $this->digitalFileService->store($data['file'], $path, 'public', 'manuals')->resource;
            $data['digital_file_id']  = $digitalFile->id;
            $digitalFile->update(['id_model' => $manual->id]);
            unset($data['file']);
        }

        $manual->update($data);

        return new ManualResource($manual->fresh('digitalFile'));
    }

    public function destroy(int $id): void
    {
        $manual = $this->find($id);
        $this->digitalFileService->destroy($manual->digital_file_id);
        $manual->delete();
    }
}
