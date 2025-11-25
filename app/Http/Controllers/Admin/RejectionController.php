<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RejectionReason;
use Illuminate\Http\Request;

class RejectionController extends Controller
{
    /**
     * Get the list of rejection reasons
     */
    public function index(Request $request)
    {
        $reason = RejectionReason::query()->latest()
            ->when(in_array($request->type, ['withdrawal']))->where('type', $request->type)->get();

        return $this->success($reason);
    }

    /**
     * Creates a new rejection reason
     */
    public function store(Request $request)
    {
        $payload = collect($request->validate(['reason' => 'required']));
        $reason = RejectionReason::create($payload->merge(['type' => 'withdrawal'])->toArray());

        return $this->success($reason);
    }

    /**
     * Update a rejection reason
     */
    public function update(Request $request, RejectionReason $rejectionReason)
    {
        $payload = $request->validate(['reason' => 'string']);
        $rejectionReason->update($payload);

        return $this->success($rejectionReason);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RejectionReason $rejectionReason)
    {
        $rejectionReason->delete();
        return $this->success(message: 'Reason deleted successfully');
    }
}
