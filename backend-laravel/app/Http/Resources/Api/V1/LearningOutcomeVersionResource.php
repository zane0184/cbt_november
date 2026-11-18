<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningOutcomeVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'is_current' => $this->is_current,
            'created_at' => $this->created_at,
        ];
    }
}
