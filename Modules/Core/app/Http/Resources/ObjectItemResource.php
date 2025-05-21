<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ObjectItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'object_type_id' => $this->object_type_id,
            'code' => $this->code,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'status' => $this->status,
            'order' => $this->order,
            'meta' => ObjectMetaResource::collection($this->whenLoaded('meta')),
        ];
    }
}