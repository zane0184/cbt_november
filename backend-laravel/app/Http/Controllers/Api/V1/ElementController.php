<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Element;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ElementController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => Element::all()]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'curriculum_id' => ['required', 'uuid', Rule::exists('curricula', 'id')->where('tenant_id', $tenantId)],
            'phase_id' => ['required', 'uuid', Rule::exists('phases', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', 'uuid', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'order_index' => 'integer'
        ]);

        $element = Element::create($validated);
        $this->auditService->log('CREATE', 'Element', $element->id, null, $element->toArray());

        return response()->json(['success' => true, 'data' => $element], 201);
    }

    public function show(Element $element)
    {
        return response()->json(['success' => true, 'data' => $element]);
    }

    public function update(Request $request, Element $element)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'order_index' => 'sometimes|integer',
            'status' => 'string'
        ]);

        $old = $element->toArray();
        $element->update($validated);
        $this->auditService->log('UPDATE', 'Element', $element->id, $old, $element->toArray());

        return response()->json(['success' => true, 'data' => $element]);
    }

    public function destroy(Element $element)
    {
        $this->auditService->log('DELETE', 'Element', $element->id, $element->toArray(), null);
        $element->delete();
        return response()->json(['success' => true]);
    }
}
