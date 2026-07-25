<?php

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'allow_admin_registration' => PlatformSetting::getSetting('allow_admin_registration', false),
            'maintenance_banner'       => PlatformSetting::getSetting('maintenance_banner', ''),
            'experimental_features'    => PlatformSetting::getSetting('experimental_features', false),
        ];

        return Inertia::render('control/Settings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'allow_admin_registration' => 'boolean',
            'maintenance_banner'       => 'string|nullable',
            'experimental_features'    => 'boolean',
        ]);

        if ($request->has('allow_admin_registration')) {
            PlatformSetting::setSetting('allow_admin_registration', $request->boolean('allow_admin_registration'));
        }

        if ($request->has('maintenance_banner')) {
            PlatformSetting::setSetting('maintenance_banner', $request->input('maintenance_banner', ''));
        }

        if ($request->has('experimental_features')) {
            PlatformSetting::setSetting('experimental_features', $request->boolean('experimental_features'));
        }

        // Log the change
        \App\Models\AdminAuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => 'update_settings',
            'entity_type' => 'platform_settings',
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'request_id'  => (string) \Illuminate\Support\Str::uuid(),
        ]);

        return back()->with('status', 'Platform settings updated successfully.');
    }
}
