<?php

namespace App\Services;

use App\Models\Giftcard;
use App\Models\GiftcardCategory;
use Exception;
use stdClass;

class SellGiftcardService
{
    private stdClass $payload;

    public function __construct()
    {
        $this->payload = new stdClass();
    }

    public function getAllGiftCards(GiftcardCategory $giftcardCategory, $payload): stdClass
    {
        try {
            $highRate = $payload->high_rate ? true : false;

            $this->payload->giftcards = Giftcard::with('giftcardCategory')
                ->when($highRate, fn ($query) => $query->where('high_rate', true))
                ->where('giftcard_category_id', $giftcardCategory->id)
                ->where('active', true)
                ->orderBy('sort_order', 'asc')
                ->get();
            $this->payload->status = 200;

            return $this->payload;
        } catch (Exception $exception) {
            $this->payload->message = 'something went wrong';
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function getAllHighRateGiftCards($payload): stdClass
    {
        try {
            $this->payload->giftcards = Giftcard::with('giftcardCategory')
                ->where('high_rate', true)
                ->where('active', true)
                ->orderBy('sort_order', 'asc')
                ->get();
            $this->payload->status = 200;

            return $this->payload;
        } catch (Exception $exception) {
            $this->payload->message = 'something went wrong';
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function getAllCategories(): stdClass
    {
        try {
            $this->payload->categories = GiftcardCategory::orderBy('sort_order', 'asc')->get();
            $this->payload->status = 200;

            return $this->payload;
        } catch (Exception $exception) {
            $this->payload->message = 'something went wrong';
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function giftcards(): stdClass
    {
        try {
            $this->payload->giftcards = GiftcardCategory::with('giftcards')->orderBy('sort_order', 'asc')->get();
            $this->payload->status = 200;

            return $this->payload;
        } catch (Exception $exception) {
            $this->payload->message = 'something went wrong';
            $this->payload->status = 500;

            return $this->payload;
        }
    }
}
