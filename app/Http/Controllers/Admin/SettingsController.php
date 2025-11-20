<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    /**
     * Get all settings or a specific setting
     */
    public function index(Request $request): JsonResponse
    {
        $key = $request->query('key');
        
        if ($key) {
            $value = AppSetting::get($key);
            return response()->json(['key' => $key, 'value' => $value]);
        }

        $settings = AppSetting::all()->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => AppSetting::get($setting->key),
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        });

        return response()->json($settings);
    }

    /**
     * Update a setting
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'value' => ['required'],
            'type' => ['nullable', 'string', 'in:string,integer,boolean,json'],
            'description' => ['nullable', 'string'],
        ]);

        $setting = AppSetting::set(
            $validated['key'],
            $validated['value'],
            $validated['type'] ?? 'string',
            $validated['description'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'setting' => [
                'key' => $setting->key,
                'value' => AppSetting::get($setting->key),
                'type' => $setting->type,
                'description' => $setting->description,
            ],
        ]);
    }

    /**
     * Get reminder email settings
     */
    public function reminderSettings(): JsonResponse
    {
        return response()->json([
            'days_before_due' => AppSetting::get('reminder_email_days_before_due', 7),
            'check_frequency' => AppSetting::get('reminder_email_check_frequency', 'daily'),
        ]);
    }

    /**
     * Update reminder email settings
     */
    public function updateReminderSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days_before_due' => ['required', 'integer', 'min:1', 'max:30'],
            'check_frequency' => ['required', 'string', 'in:daily,weekly'],
        ]);

        AppSetting::set(
            'reminder_email_days_before_due',
            $validated['days_before_due'],
            'integer',
            'Number of days before repayment due date to send reminder email'
        );

        AppSetting::set(
            'reminder_email_check_frequency',
            $validated['check_frequency'],
            'string',
            'How often to check for repayments due (daily or weekly)'
        );

        return response()->json([
            'success' => true,
            'message' => 'Reminder email settings updated successfully',
        ]);
    }
}
