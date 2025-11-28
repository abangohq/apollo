<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Nova\Nova;

/** @property \App\Models\Giftcard $resource */
class GiftCardResource extends JsonResource
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
            // * return the rates in non-minor unit
            'rate' => $this->resource->rate / 100,
            'status' => $this->resource->active ? 'Active' : 'Inactive',
            'updated_at' => $this->resource->updated_at,
            'lastUpdated' => $this->resource->updated_at->timezone(
                Nova::resolveUserTimezone($request)
            )->toDayDateTimeString(),
            'category' => new GiftCardCategoryResource($this->whenLoaded('giftcardCategory')),
            'resourceURL' => route('nova.pages.detail', [
                'resource' => 'subcategories',
                'resourceId' => $this->resource->id,
            ]),
        ];
    }
}
