<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ObjectMetaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'object_id' => $this->object_id,
            'key' => $this->key,
            'value' => $this->value,
        ];
    }
}