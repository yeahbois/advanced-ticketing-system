<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class OpenHouseController extends Controller {
    protected $sheetService;
    public function __construct() {
        $this->sheetService = new GoogleSheetService("1QQ6Ora27n5_5Xbf0-0B2EyPnW2i7ozPtNl2YOpZOwbc");
    }

    // DATABASE HANDLER
    public function importDynamicTable(array $rows, string $tableName) {
        // 1. Get header row
        $columns = [
        "EMAIL",
        "NAMA_LENGKAP",
        "ASAL_SEKOLAH",
        "KELAS",
        "NOMOR_WHATSAPP",
        "ID_LINE",
        "NOMOR_WHATSAPP_WALI",
        "FOTO_DIRI",
        "JENIS_TIKET",
        "KEYCHAIN",
        "NOTEBOOK",
        "FOLDABLE_BAG",
        "TOPI",
        "TUMBLR",
        "STIKER",
        "BUNDLE_1",
        "BUNDLE_2",
        "BUNDLE_3",
        "METODE_PEMBAYARAN",
        "BUKTI_PEMBAYARAN",
        "BANK SOAL",
        "METODE_PEMBAYARAN_BANKSOAL",
        "BUKTI_PEMBAYARAN_BANKSOAL",
        "ID_KELOMPOK",
        "STATUS_TIKET",
        "HADIR_ANAK",
        "HADIR_OTM1",
        "HADIR_OTM2",
        "RUANG_OTM1",
        "RUANG_OTM2",
        "ID_TIKET"
    ];

        // 2. Drop table if exists (optional, but recommended)
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }

        // 3. Create table dynamically
        Schema::create($tableName, function (Blueprint $table) use ($columns) {
            $table->id();

            foreach ($columns as $column) {
                $cleanColumn = \Str::slug($column, '_'); // clean name
                $table->string($cleanColumn)->nullable();
            }

            $table->timestamps();
        });

        // 4. Insert data rows
        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $row) {
            $insertData = [];

            foreach ($columns as $index => $column) {
                $cleanColumn = \Str::slug($column, '_');
                $insertData[$cleanColumn] = $row[$index] ?? null;
            }

            DB::table($tableName)->insert($insertData);
        }

        return true;
    }

    public function syncToSQLDatabase() {
        $data = $this->sheetService->getAllData("DATABASE!A1:AZ1000");
        $parsedData = [];
        for ($i = 0; $i < count($data); $i++) {
            if (isset($data[$i][0]) && !empty($data[$i][0])) {
                $parsedData[] = $data[$i];
            }
        }
        $this->importDynamicTable($parsedData, 'database_open_house');
        return response()->json(['status' => 'success', 'data' => $parsedData]);
    }

    // DEBUG
    public function getOpenHouseData() {
        $data = $this->sheetService->getAllData("DATABASE!A1:AZ1000");
        return response()->json($data);
    }

    public function getParsedData() {
        $data = $this->sheetService->getAllData("DATABASE!A1:AZ1000");
        $parsedData = [];
        for ($i = 0; $i < count($data); $i++) {
            if (isset($data[$i][0]) && !empty($data[$i][0])) {
                $parsedData[] = $data[$i];
            }
        }
        $parsedData[] = ["Total Responses" => count($parsedData)];
        return response()->json($parsedData);
    }

    public function getOpenHouseStats() {
        $data = $this->sheetService->getAllData("DATABASE!A1:AZ1000");
        $total = 0;
        for ($i = 0; $i < count($data); $i++) {
            if (isset($data[$i][0]) && !empty($data[$i][0])) {
                $total++;
            }
        }
        return response()->json(['total' => $total]);
    }

    public function sqlSearchLogic(Request $request){
        $name = $request->input('name');
        $email = $request->input('email');

        $query = DB::table('database_open_house');

        if (!empty($name)) {
            $query->whereRaw('LOWER(nama_lengkap) LIKE ?', ['%' . strtolower($name) . '%']);
        }

        if (!empty($email)) {
            $query->whereRaw('LOWER(email) LIKE ?', ['%' . strtolower($email) . '%']);
        }

        $result = $query->first();

        return response()->json($result);
    }


    // SQL API
    public function sqlGetAllData() {
        $data = DB::table('database_open_house')->get();
        return response()->json($data);
    }
    
    public function sqlCheckTicket(Request $request) {
        $query = [
            'name' => $request->name,
            'email' => $request->email
        ];
        $request = DB::table('database_open_house');
        if (isset($query['name'])) {
            $request->where('nama_lengkap', '=', $query['name']);
        }

        if (isset($query['email'])) {
            $request->where('email', '=', $query['email']);
        }

        $result = $request->first();

        if ($result) {
            return response()->json(['found' => true, 'data' => $result]);
        } else {
            return response()->json(['found' => false]);
        }
    }

    public function showTicketDataPage(Request $request) {
        // $query = [
        //     'name' => $request->name,
        //     'email' => $request->email
        // ];
        // $request = DB::table('database_open_house');
        // if (isset($query['name'])) {
        //     $request->where('nama_lengkap', '=', $query['name']);
        // }

        // if (isset($query['email'])) {
        //     $request->where('email', '=', $query['email']);
        // }

        // $result = $request->first();

        $name = $request->input('name');
        $email = $request->input('email');

        $query = DB::table('database_open_house');

        if (!empty($name)) {
            $query->whereRaw('LOWER(nama_lengkap) LIKE ?', ['%' . strtolower($name) . '%']);
        }

        if (!empty($email)) {
            $query->whereRaw('LOWER(email) LIKE ?', ['%' . strtolower($email) . '%']);
        }

        $result = $query->first();

        return view('sementara.openhouse_ticket_data', [
            'status' => $result ? 'found' : 'not_found',
            'data' => $result
        ]);
    }

    public function sqlOHPresent(Request $request) {
        $qrString = $request->qrString;

        if (empty($qrString)) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR code kosong'
            ], 400);
        }

        // =========================
        // 1. PARSE QR STRING
        // =========================
        // Format: ROLE_TICKETID (e.g. ANAK_ABCD1234)
        $parts = explode('_', $qrString, 2);

        if (count($parts) !== 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format QR tidak valid'
            ], 400);
        }

        [$qrRole, $ticketId] = $parts;

        // =========================
        // 2. FIND TICKET IN DB
        // =========================
        $data = DB::table('database_open_house')
            ->where('id_tiket', $ticketId)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket tidak ditemukan'
            ], 404);
        }

        // =========================
        // 3. ROLE MAP (SOURCE OF TRUTH)
        // =========================
        $TICKET_ROLE_MAP = [
            "Tiket ANAK saja" => ["ANAK"],
            "Tiket ANAK + 1 ORANG TUA/WALI" => ["ANAK", "OTM1"],
            "Tiket ANAK + 2 ORANG TUA/WALI" => ["ANAK", "OTM1", "OTM2"],
        ];

        $jenisTiketDb = $data->jenis_tiket;

        if (!isset($TICKET_ROLE_MAP[$jenisTiketDb])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jenis tiket tidak dikenali di sistem'
            ], 500);
        }

        $allowedRoles = $TICKET_ROLE_MAP[$jenisTiketDb];

        // =========================
        // 4. VALIDATE ROLE
        // =========================
        if (!in_array($qrRole, $allowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role QR tidak sesuai dengan jenis tiket'
            ], 403);
        }

        // =========================
        // 5. SUCCESS, PRESENT LOGIC
        // =========================
        if ($qrRole == "ANAK") {
            if ($data->hadir_anak == "HADIR") {
                return response()->json([
                    'status' => 'error',
                    'message' => "[ANAK] {$data->nama_lengkap} sudah tercatat HADIR"
                ], 403);
            }
            DB::table('database_open_house')
            ->where('id_tiket', $ticketId)
            ->update(['hadir_anak' => "HADIR", 'updated_at' => now()]);
            return response()->json([
                'status' => 'berhasil',
                'message' => "[ANAK] {$data->nama_lengkap} BERHASIL dicatat HADIR"
            ]);
        } elseif ($qrRole == "OTM1") {
            if ($data->hadir_otm1 == "HADIR") {
                return response()->json([
                    'status' => 'error',
                    'message' => "[WALI 1] {$data->nama_lengkap} sudah tercatat HADIR"
                ], 403);
            }
            DB::table('database_open_house')
            ->where('id_tiket', $ticketId)
            ->update(['hadir_otm1' => "HADIR", 'updated_at' => now()]);
            return response()->json([
                'status' => 'berhasil',
                'message' => "[WALI 1] {$data->nama_lengkap} BERHASIL dicatat HADIR, ruangan ada di {$data->ruang_otm1}"
            ]);
        } elseif ($qrRole == "OTM2") {
            if ($data->hadir_otm2 == "HADIR") {
                return response()->json([
                    'status' => 'error',
                    'message' => "[WALI 2] {$data->nama_lengkap} sudah tercatat HADIR"
                ], 403);
            }
            DB::table('database_open_house')
            ->where('id_tiket', $ticketId)
            ->update(['hadir_otm2' => "HADIR", 'updated_at' => now()]);
            return response()->json([
                'status' => 'berhasil',
                'message' => "[WALI 2] {$data->nama_lengkap} BERHASIL dicatat HADIR, ruangan ada di {$data->ruang_otm2}"
            ]);
        }
    }


    public function apiTicketCreated(Request $request) {
        return response()->json(['status' => 'success'], 200);
    }

    public function sqlUpdateTicketStatus(Request $request) {
        $params = $request->ticket_id;
        $status = $request->status;
        if (!$params) {
            return response()->json(['status' => 'error', 'message' => 'ticket_id parameter is required'], 400);
        }
        $updated = DB::table('database_open_house')
            ->where('id_tiket', $params)
            ->update(['status_tiket' => $status, 'updated_at' => now()]);
        if ($updated) {
            return response()->json(['status' => 'success'], 200);
        } else {
            return response()->json(['status' => 'error', 'message' => 'No matching ticket found'], 404);
        }
    }

    // SHEETS API
    public function sheetsCheckTicket($name) {
        $data = $this->sheetService->getAllData("DATABASE!A1:AZ1000");
        for ($i = 0; $i < count($data); $i++) {
            if (isset($data[$i][1]) && strtolower($data[$i][2]) === strtolower($name)) {
                return response()->json(['found' => true, 'data' => $data[$i]]);
            }
        }
        return response()->json(['found' => false]);
    }

    public function checkSheetsBaru() {
        $data = $this->sheetService->getAllData("DATABASE!A1:AZ1000");
        return response()->json($data);
    }
}