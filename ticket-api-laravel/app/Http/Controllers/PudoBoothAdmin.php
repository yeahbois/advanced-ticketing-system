<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PudoBoothAdmin extends Controller
{
    private function updateUrlField($field, $url)
    {
        try {
            $setting = Setting::firstOrNew(['type' => 'url']);
            $value = $setting->value ?? [];
            $value[$field] = $url;
            $setting->value = $value;
            return $setting->save();
        } catch (\Exception $e) {
            Log::error("Failed to update $field: " . $e->getMessage());
            return false;
        }
    }

    public function changeDriveURL($url)
    {
        return $this->updateUrlField('driveURL', $url);
    }

    public function changeSpreadsheetURL($url)
    {
        return $this->updateUrlField('spreadSheetURL', $url);
    }

    public function changeFrameURL($url)
    {
        return $this->updateUrlField('frameCDNURL', $url);
    }

    public function getPreset($id)
    {
        try {
            $preset = Setting::where('type', 'preset')->where('preset', (int)$id)->first();

            if (!$preset) {
                return response()->json(['error' => 'Preset not found'], 404);
            }

            return response()->json([
                'preset' => $preset->preset,
                'values' => $preset->value ?? []
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get preset $id: " . $e->getMessage());
            return response()->json(['error' => 'Failed to get preset'], 500);
        }
    }

    public function changePresetSetting(Request $request, $id)
    {
        try {
            $data = $request->all();

            $setting = Setting::updateOrCreate(
                ['type' => 'preset', 'preset' => (int)$id],
                ['value' => $data]
            );

            if ($setting) {
                return response()->json([
                    'success' => true,
                    'message' => "Preset $id updated successfully"
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "No changes made to preset $id"
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update preset $id: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update preset'], 500);
        }
    }

    public function getURL($data)
    {
        $setting = Setting::where('type', 'url')->first();
        if (!$setting) {
            return response()->json(["error" => "not found"]);
        }
        $urls = $setting->value;

        if ($data == "drive") {
            return $urls['driveURL'] ?? null;
        } else if ($data == "spreadsheet") {
            return $urls['spreadSheetURL'] ?? null;
        } else if ($data == "frame") {
            return $urls['frameCDNURL'] ?? null;
        } else {
            return response()->json(["error" => "not found"]);
        }
    }
}
