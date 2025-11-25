<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Models\SystemSetting;
use App\Models\SystemStatus;
use App\Repositories\GeneralRepository;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Set the application withdrawal limit
     */
    public function withdrawLimit(Request $request)
    {
        $request->validate(['limit' => ['required', 'numeric']]);
        $limit = SystemSetting::where('key', 'max_automatic_withdrawal_amount')->firstOrFail();
        $limit->update(['value' => $request->limit]);

        return $this->success($limit, 'Limit Set Successfully.');
    }

    /**
     * Set the application withdrawal mode
     */
    public function withdrawalMode(Request $request)
    {
        $request->validate(['mode' => ['required', 'in:automatic,manual']]);
        $mode = SystemSetting::where('key', 'withdrawal_mode')->firstOrFail();
        $mode->update(['value' => $request->mode]);

        return $this->success($mode, 'Withdrawal mode set successfully.');
    }

    /**
     * Set payment status 
     */
    public function setSweepAddress(Request $request)
    {
        $request->validate(['address' => 'required']);
        $sweep = SystemSetting::where('key', 'sweep_address')->firstOrFail();
        $sweep->update(['value' => $request->address]);

        return $this->success($sweep, 'Sweep address updated successfully.');
    }

    /**
     * Retrieve all settings
     */
    public function settings(Request $request)
    {
        $settings = SystemSetting::all();
        return $this->success($settings);
    }

    /**
     * Set withdrawal payment gateway to use
     */
    public function setPaymentGateway(Request $request)
    {
        $request->validate(['gateway' => ['required', 'in:redbiller,monnify']]);
        $gateway = SystemSetting::where('key', 'payment_gateway')->firstOrFail();
        $gateway->update(['value' => $request->gateway]);

        return $this->success($gateway, 'Payment gateway set successfully.');
    }

    /**
     * Retrieve withdrawal payment settings
     */
    public function paymentSettings(Request $request)
    {
        $settings = GeneralRepository::paySettings();

        return $this->success($settings, 'Payment Settings');
    }

    /**
     * Set the mobile application min version
     */
    public function setVersion(Request $request)
    {
        $version = AppVersion::create($request->all());

        return $this->success($version, 'Version set successfully');
    }

    /**
     * Retrieve the mobile min version
     */
    public function versions(Request $request)
    {
        $version = AppVersion::all();

        return $this->success($version, 'Minimum app version');
    }

    /**
     * Retrive the system status
     */
    public function systemStatus(Request $request)
    {
        $settings = SystemStatus::all();

        return $this->success($settings, 'System status');
    }

    /**
     * Set the platform status for mobile
     */
    public function setPlatformStatus(Request $request, SystemStatus $systemStatus)
    {
        $systemStatus->update($request->all());

        return $this->success($systemStatus, 'Status set Successfully.');
    }
}
