<?php

namespace App\Services;

use Google\Client as Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;
use Illuminate\Support\Facades\Log;
use Google\Service\Sheets\ValueRange;

class GoogleSheetService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;

    public function __construct($spreadsheet)
    {
        $this->client = new \Google_Client();
        $this->client->setAuthConfig(storage_path('credentials.json'));
        $this->client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $this->client->useApplicationDefaultCredentials(); // ✅ key line

        $this->service = new \Google_Service_Sheets($this->client);
        $this->spreadsheetId = $spreadsheet;
    }

    public function getAllData($range)
    {
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        return $response->getValues();
    }

    public function appendData(array $data)
    {
        // Debug: Log the received data
        Log::info("Received data: " . json_encode($data));

        // Convert associative array to indexed array
        $values = [
            array_values($data) // Extract values only
        ];

        // Debug: Log the formatted data
        Log::info("Formatted data: " . json_encode($values));

        // Set the range to append data to (adjust if necessary)
        $range = 'Sheet1!A1';

        // Prepare the data to be appended
        $body = new ValueRange([
            'values' => $values,
        ]);

        // Set the input option to RAW (no formatting)
        $params = ['valueInputOption' => 'RAW'];

        try {
            // Append the data to the spreadsheet
            $this->service->spreadsheets_values->append($this->spreadsheetId, $range, $body, $params);
            Log::info("Data appended successfully!");
        } catch (\Exception $e) {
            // Catch any errors and log the error message
            Log::error("Error appending data: " . $e->getMessage());
        }
    }
}
