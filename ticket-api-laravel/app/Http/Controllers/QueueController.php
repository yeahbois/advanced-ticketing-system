<?php

namespace App\Http\Controllers;

use App\Models\Pbqueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FormController;
use App\Services\GoogleSheetService;

class QueueController extends Controller
{
    /** Get all data, sorted by orderNo */
    public function getAllData()
    {
        $data = Pbqueue::orderBy('orderNo')->get();
        return response()->json($data);
    }

    /** Add a new entry to the queue (always at bottom) */
    public function appendData(Request $request)
    {
        $data = $request->only(['nama', 'tipe_antrian']);
        if (Pbqueue::where('nama', $data['nama'])->exists()) {
            return response()->json(['success' => false, 'message' => 'data sudah ada'], 500);
        }

        $maxOrder = Pbqueue::max('orderNo') ?? 0;

        $entry = Pbqueue::create([
            'nama' => $data['nama'],
            'tipe' => $data['tipe_antrian'],
            'orderNo' => $maxOrder + 1,
        ]);

        return response()->json(['success' => true, 'data' => $entry]);
    }

    /** Remove by name */
    public function removeData($name)
    {
        return DB::transaction(function () use ($name) {
            $target = Pbqueue::where('nama', $name)->first();
            if (!$target) {
                return response()->json(['error' => 'Not found'], 404);
            }

            $orderNo = $target->orderNo;
            $target->delete();

            Pbqueue::where('orderNo', '>', $orderNo)->decrement('orderNo');

            return response()->json(['success' => true]);
        });
    }

    /** Search by name (partial) */
    public function searchByName($name)
    {
        $results = Pbqueue::where('nama', 'LIKE', "%{$name}%")
            ->orderBy('orderNo')
            ->get();
        return response()->json($results);
    }

    /** Move up by one */
    public function moveUp($name)
    {
        return DB::transaction(function () use ($name) {
            $current = Pbqueue::where('nama', $name)->first();
            if (!$current) return response()->json(['error' => 'Not found'], 404);
            if ($current->orderNo <= 1) return response()->json(['error' => 'Already at top'], 400);

            $prev = Pbqueue::where('orderNo', $current->orderNo - 1)->first();
            if (!$prev) return response()->json(['error' => 'Previous item not found'], 500);

            // Swap order numbers
            $current->orderNo--;
            $prev->orderNo++;
            $current->save();
            $prev->save();

            return response()->json(['success' => true]);
        });
    }

    /** Move down by one */
    public function moveDown($name)
    {
        return DB::transaction(function () use ($name) {
            $current = Pbqueue::where('nama', $name)->first();
            if (!$current) return response()->json(['error' => 'Not found'], 404);

            $next = Pbqueue::where('orderNo', $current->orderNo + 1)->first();
            if (!$next) return response()->json(['error' => 'Already at bottom'], 400);

            // Swap order numbers
            $current->orderNo++;
            $next->orderNo--;
            $current->save();
            $next->save();

            return response()->json(['success' => true]);
        });
    }

    /** Move to top */
    public function moveToTop($name)
    {
        return DB::transaction(function () use ($name) {
            $current = Pbqueue::where('nama', $name)->first();
            if (!$current) return response()->json(['error' => 'Not found'], 404);

            Pbqueue::where('orderNo', '<', $current->orderNo)->increment('orderNo');
            $current->orderNo = 1;
            $current->save();

            return response()->json(['success' => true]);
        });
    }

    /** Move to bottom */
    public function moveToBottom($name)
    {
        return DB::transaction(function () use ($name) {
            $current = Pbqueue::where('nama', $name)->first();
            if (!$current) return response()->json(['error' => 'Not found'], 404);

            $maxOrder = Pbqueue::max('orderNo') ?? 0;

            Pbqueue::where('orderNo', '>', $current->orderNo)->decrement('orderNo');
            $current->orderNo = $maxOrder;
            $current->save();

            return response()->json(['success' => true]);
        });
    }

    /** Mark as completed */
    public function complete(Request $request)
    {
        $name = $request->input('name');
        $jumlah = $request->input('jumlah');

        $dataPesan = Pbqueue::where('nama', $name)->first();
        if (!$dataPesan) {
             \Log::info('Queue item not found for completion: ' . $name);
             return response()->json(['error' => 'Not found'], 404);
        }

        $data = new \Illuminate\Http\Request([
            'Nama' => $dataPesan->nama,
            'Jumlah Pesanan' => $jumlah
        ]);

        // Assuming GoogleSheetService and FormController are correctly set up
        $googleSheetService = new GoogleSheetService("1MYDCEtoS0BLec9WCfFAhlZoL9txz1QIXnZTQXeDOrHs");
        $formController = new FormController($googleSheetService);
        return $formController->submitForm($data);
    }
}
