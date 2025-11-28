<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property \App\Models\GiftcardCategory $resource */
class GiftCardCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        GiftCardResource::withoutWrapping();

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
        ];
    }
}
