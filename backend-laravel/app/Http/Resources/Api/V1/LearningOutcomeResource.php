<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningOutcomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'curriculum_id' => $this->curriculum_id,
            'phase_id' => $this->phase_id,
            'subject_id' => $this->subject_id,
            'element_id' => $this->element_id,
            'current_version' => new LearningOutcomeVersionResource($this->whenLoaded('currentVersion')),
            'versions' => LearningOutcomeVersionResource::collection($this->whenLoaded('versions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
