<?php

namespace App\Services;

use App\Enums\Status;
use App\Enums\TransactionType;
use App\Mail\TradeNotification;
use App\Models\Giftcard;
use App\Models\Trade;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use stdClass;

class TradeService
{
    private User $user;

    private stdClass $payload;

    public function __construct()
    {
        $this->user = new User();
        $this->payload = new stdClass();
    }

    public function create($payload): stdClass
    {
        try {
            $giftcard = Giftcard::findOrFail($payload->giftcard_id);

            if ($payload->amount < $giftcard->minimum_amount) {
                $this->payload->message = 'amount is less than minimum amount';
                $this->payload->status = 406;

                return $this->payload;
            }

            if ($payload->amount > $giftcard->maximum_amount) {
                $this->payload->message = 'amount is greater than maximum amount';
                $this->payload->status = 406;

                return $this->payload;
            }

            $user = $payload->user();

            $transaction = $user->transactions()->create([
                'type' => TransactionType::SELL_GIFTCARD->value,
                'amount' => ($payload->amount * $giftcard->rate * $payload->units)/100,
                'charge' => 0,
                'currency' => 'NGN',
                'reference' => generateReference(),
                'status' => Status::PENDING->value,
                'credit' => true,
                'provider_response' => json_encode(null),
            ]);

            $trade = $user->trades()->create([
                'transaction_id' => $transaction->id,
                'giftcard_id' => $giftcard->id,
                'amount' => $payload->amount,
                'units' => $payload->units,
                'currency' => $giftcard->currency,
                'rate' => $giftcard->rate,
                'e_code' => $payload->e_code ?? null,
                'payout_method' => $payload->payout_method,
            ]);

            $transaction->update(['transactable_id' => $trade->id]);

            if ($payload->hasFile('images') && is_array($payload->file('images'))) {
                foreach ($payload->file('images') as $image) {
                    try {
                        $trade->images()->create([
                            'image_url' => cloudinary()->upload($image->getRealPath(), [
                                'folder' => 'hannah/trades',
                            ])->getSecurePath(),
                        ]);
                    } catch (Exception $e) {
                        \Log::error('Image upload failed: ' . $e->getMessage());
                    }
                }
            }


            $admins = User::where('type', ['admin'])->get();

            foreach ($admins as $admin) {
                \Mail::to($admin->email)->queue(new TradeNotification($trade));
            }

            $this->payload->trade = $trade;
            $this->payload->status = 201;

            return $this->payload;
        } catch (Exception $exception) {
            \Log::info('trade failed', [$exception]);
            $this->payload->message = 'something went wrong';
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function view(Trade $trade): stdClass
    {
        try {
            if ($trade->user()->is(Auth::user())) {
                $this->payload->trade = $trade->with('approvals');
                $this->payload->status = 200;

                return $this->payload;
            }

            $this->payload->message = 'trade not found';
            $this->payload->status = 404;

            return $this->payload;
        } catch (Exception $exception) {
            $this->payload->message = 'something went wrong';
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function getAll($payload): stdClass
    {
        try {
            $user = $this->user->find($payload->user()->id);

            $status = $payload->status ? trim($payload->status) : null;
            $date = $payload->date ? trim($payload->date) : null;
            $ranges = $payload->ranges ? trim($payload->ranges) : null;

            $trades = $user->trade()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($date, function ($query, $date) {
                    return $query->whereDate('created_at', '>=', match ($date) {
                        'today' => now()->toDateString(),
                        'last_week' => now()->subWeek()->toDateString(),
                        'last_30_days' => now()->subDays(30)->toDateString(),
                        default => now()->subYears(100)->toDateString()
                    });
                })
                ->when($ranges, function ($query, $ranges) {
                    $ranges = explode(',', $ranges);

                    return $query->whereDate('created_at', '>=', Carbon::parse($ranges[0])->toDateTimeString())
                        ->whereDate('created_at', '<=', Carbon::parse($ranges[1])->toDateTimeString());
                })
                ->with(['giftcard', 'images', 'giftcard.giftcardCategory'])
                ->orderBy('created_at', 'DESC');

            $this->payload->trades = $trades;
            $this->payload->status = 200;

            return $this->payload;
        } catch (Exception $exception) {
            $this->payload->message = 'something went wrong';
            $this->payload->status = 500;

            return $this->payload;
        }
    }
}
