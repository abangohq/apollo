<?php

namespace App\Http\Controllers\BulkGiftcardRatesEditor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\GiftCardCategoryResource;
use App\Http\Resources\Admin\GiftCardResource;
use App\Models\Giftcard;
use App\Models\GiftcardCategory;
use Faradele\GctnGiftcardRatesEditor\Events\GiftCardRateBulkEditedEvent;
use Illuminate\Cache\Lock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

class EditorController extends Controller
{
    const MAXIMUM_RATE_VALUE = 2_500;

    public static $rateFields = ['rate'];

    public function loadConfig()
    {
        $this->authorize('giftCardRateEditorView');

        return [
            'rateFields' => self::$rateFields,
            'editableFields' => self::$rateFields,
            'categories' => GiftCardCategoryResource::collection(
                GiftcardCategory::select(['id', 'name'])->get()
            ),
            'lockTimeoutMins' => 5,
        ];
    }

    public function loadGiftcards(Request $request)
    {
        $this->authorize('giftCardRateEditorView');

        if ($request->boolean('reload')) {
            Cache::forget('__bulkRateEditor:LastUpdatedBy');
        }

        return GiftCardResource::collection(
            Giftcard::with('giftcardCategory:id,name')
                ->where('giftcards.active', true)
                ->orderBy('giftcards.sort_order')
                ->select([
                    'id', 'name', 'rate', 'active', 'giftcard_category_id',
                    'updated_at',
                ])
                ->get()
        );
    }

    public function updateManyGiftcards(Request $request)
    {
        $this->authorize('giftCardRateEditorUpdate');

        $rules = [
            'data' => ['required', 'array'],
            'data.*.id' => ['required', 'uuid'],
        ];
        collect(self::$rateFields)
            ->each(function ($field) use (&$rules) {
                $rules["data.*.{$field}"] = ['required', 'numeric', 'min:0', 'max:'.self::MAXIMUM_RATE_VALUE];
            });

        $request->validate($rules, [
            'data.*.rate.max' => 'Maximum allowed rate value is '.self::MAXIMUM_RATE_VALUE.'.',
        ]);

        // * ensure another user has not updated rates before us
        if (! $this->ensureAnotherUserHasNotUpdatedRates()) {
            return response()->json([
                'message' => "Another user has updated rates before you. Please use the 'Reload' button and try again.",
            ], 409);
        }

        if (! $lock = $this->acquireLock()) {
            return response()->json([
                'message' => 'Another user is currently updating rates. Please try again later.',
            ], 400);
        }

        try {
            collect($request->get('data'))
                ->each(function (array $info) {
                    $gc = GiftCard::find($info['id']);
                    if ($gc) {
                        $gc->update(
                            collect(self::$rateFields)
                                // * convert the rates to minor unit
                                ->mapWithKeys(fn ($field) => [$field => floatval($info[$field]) * 100])
                                ->toArray()
                        );
                    }
                });

            // * this project doesn't appear to support event broadcasting
            // * we will use a little workaround instead for preventing overwriting
            // * other user's changes: ensureAnotherUserHasNotUpdatedRates();
            // GiftCardRateBulkEditedEvent::dispatch(Auth::user());

            $this->setLastBulkRateUpdatedBy();

            // activity()
            //     ->withProperties(['giftcards' => $request->get('data')])
            //     ->log('Submitted bulk giftcard rate edit operation.');
        } catch (Throwable $e) {
            info($e);

            return response()->json([
                'message' => 'An error occurred while updating giftcard rates.',
            ], 500);
        } finally {
            $lock?->release();
        }
    }

    public function updateSingleGiftcard(Request $request)
    {
        $this->authorize('giftCardRateEditorUpdate');

        $rules = ['id' => ['required', 'uuid']];
        collect(self::$rateFields)
            ->each(function ($field) use (&$rules) {
                $rules[$field] = ['required', 'numeric', 'min:0', 'max:'.self::MAXIMUM_RATE_VALUE];
            });

        $request->validate($rules, [
            'rate.max' => 'Maximum allowed rate value is '.self::MAXIMUM_RATE_VALUE.'.',
        ]);

        if (! $this->ensureAnotherUserHasNotUpdatedRates()) {
            return response()->json([
                'message' => "Another user has updated rates before you. Please use the 'Reload' button and try again.",
            ], 409);
        }

        if (! $lock = $this->acquireLock()) {
            return response()->json([
                'message' => 'Another user is currently updating rates. Please try again later.',
            ], 400);
        }

        try {
            $giftcard = GiftCard::findOrFail($request->id);
            $giftcard->forceFill($request->only(self::$rateFields));
            $giftcard->update(
                collect(self::$rateFields)
                    // * convert the rates to minor unit
                    ->mapWithKeys(fn ($field) => [$field => floatval($request->input($field)) * 100])
                    ->toArray()
            );
            $giftcard->save();

            // * this project doesn't appear to support event broadcasting
            // * we will use a little workaround instead for preventing overwriting
            // * other user's changes: ensureAnotherUserHasNotUpdatedRates();
            // GiftCardRateBulkEditedEvent::dispatch(Auth::user());

            $this->setLastBulkRateUpdatedBy();

            // activity()
            //     ->performedOn($giftcard)
            //     ->log('Submitted single giftcard rate edit operation.');

            return new GiftCardResource($giftcard->fresh(['category']));
        } catch (Throwable $e) {
            info($e);

            return response()->json([
                'message' => 'An error occurred while updating giftcard rate.',
            ], 500);
        } finally {
            $lock?->release();
        }
    }

    private function ensureAnotherUserHasNotUpdatedRates()
    {
        $lastBulkRateUpdatedBy = Cache::get('__bulkRateEditor:LastUpdatedBy', null);
        if (! is_null($lastBulkRateUpdatedBy)
            and $lastBulkRateUpdatedBy !== Auth::user()->username
        ) {
            return false;
        }

        return true;
    }

    private function setLastBulkRateUpdatedBy()
    {
        // ! this is now redundant since we set the same last update by value
        // ! from the GiftCard model's updated event in order to cover use case
        // ! where the rate was updated using the normal giftcard nova edit form
        // ! and not our rate editor
        // Cache::put('__bulkRateEditor:LastUpdatedBy', Auth::user()->username);
    }

    private function acquireLock(): ?Lock
    {
        $lock = Cache::lock('bulk-rate-editor-update-lock', 30);
        if (! $lock->get()) {
            return null;
        }

        return $lock;
    }
}
