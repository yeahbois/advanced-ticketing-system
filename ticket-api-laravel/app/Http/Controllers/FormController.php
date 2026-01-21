<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class FormController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

    public function submitForm(Request $data)
    {
        // Append data to Google Sheets
        $this->googleSheetService->appendData($data->all());

        return redirect('/')->with('success', 'Jawaban terkirim, tunggu konfirmasi lebih lanjut!');
    }
}
