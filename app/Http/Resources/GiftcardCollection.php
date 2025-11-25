<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftcardCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => new GiftcardCategoryCollection($this->giftcardCategory),
            'image' => $this->image,
            'wait_time' => $this->wait_time,
            'minimum_amount' => $this->minimum_amount,
            'maximum_amount' => $this->maximum_amount,
            'currency' => $this->currency,
            'high_rate' => (bool) $this->high_rate,
            'active' => (bool) $this->active,
            'sort_order' => $this->sort_order,
            'terms' => $this->terms,
            'rate' => $this->rate,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
