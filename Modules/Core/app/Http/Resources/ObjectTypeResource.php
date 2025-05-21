<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ObjectTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'order' => $this->order,
            'object_items' => ObjectItemResource::collection($this->whenLoaded('object_items')),
        ];
    }
}