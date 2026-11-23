<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Material;

class MaterialController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Material::class);

        return response()->json([
            'success' => true,
            'data' => Material::query()->paginate(),
        ]);
    }

    public function show(Material $material)
    {
        $this->authorize('view', $material);

        return response()->json([
            'success' => true,
            'data' => $material,
        ]);
    }
}
