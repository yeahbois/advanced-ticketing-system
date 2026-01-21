<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Google\Service\Drive;

class GoogleController extends Controller
{
    private function getClient()
    {
        $client = new GoogleClient();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(config('google.redirect_uri'));
        $client->addScope(Drive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        return $client;
    }

    // 1️⃣ Admin starts the authorization
    public function redirectToGoogle(Request $request)
    {
        // Optional security check
        if ($request->query('key') !== config('google.admin_secret')) {
            abort(403, 'Unauthorized');
        }

        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    // 2️⃣ Google redirects back here
    public function handleCallback(Request $request)
    {
        $client = $this->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        if (isset($token['error'])) {
            abort(400, 'Google OAuth error: ' . $token['error_description']);
        }

        // Store tokens
        \DB::table('google_tokens')->updateOrInsert(
            ['id' => 1],
            [
                'access_token' => json_encode($token),
                'refresh_token' => $token['refresh_token'] ?? \DB::table('google_tokens')->where('id', 1)->value('refresh_token'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return '✅ Google Drive connected successfully!';
    }

    // 3️⃣ Use the saved token to upload files (used by photobooth)
    public static function uploadToDrive($filePath, $fileName, $folderId)
    {
        $client = (new self)->getClient();
        $tokenRow = \DB::table('google_tokens')->where('id', 1)->first();
        if (!$tokenRow) {
            throw new \Exception('Google token not found. Connect your account first.');
        }
        // Make sure access_token is a string
        $accessTokenJson = is_string($tokenRow->access_token)
            ? $tokenRow->access_token
            : json_encode($tokenRow->access_token);

        $tokenData = json_decode($accessTokenJson, true);
        $client->setAccessToken($tokenData);
        // Refresh if expired
        if ($client->isAccessTokenExpired()) {
            if (!empty($tokenRow->refresh_token)) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($tokenRow->refresh_token);
                \DB::table('google_tokens')->where('id', 1)->update([
                    'access_token' => json_encode($newToken),
                ]);
                $client->setAccessToken($newToken);
            } else {
                throw new \Exception('No refresh token found. Reconnect Google Drive.');
            }
        }

        $service = new Drive($client);
        $fileMetadata = new Drive\DriveFile([
            'name' => $fileName,
            'parents' => [$folderId]
        ]);

        $content = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath); // auto-detect
        $file = $service->files->create($fileMetadata, [
            'data' => file_get_contents($filePath),
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, name, webViewLink',
            'supportsAllDrives' => true
        ]);

        return $file->id;
    }

    public function uploadPhoto(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $folderId = config('google.drive_id'); // set your folder ID in .env

        try {
            $fileId = self::uploadToDrive($file->getPathname(), $file->getClientOriginalName(), $folderId);
            return response()->json([
                'id' => $fileId,
                'name' => $file->getClientOriginalName(),
                'webViewLink' => "https://drive.google.com/file/d/$fileId/view"
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
