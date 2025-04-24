<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DropboxService
{
    protected $accessToken;
    
    public function __construct()
    {
        $this->accessToken = config('services.dropbox.access_token');
    }
    
    /**
     * Upload a file to Dropbox and return the shared URL
     *
     * @param UploadedFile $file
     * @param string $path Destination path in Dropbox
     * @return string|null URL of the uploaded file or null if upload failed
     */
    public function uploadFile(UploadedFile $file, string $path = '/place-images')
    {
        try {
            // Generate a unique filename to avoid conflicts
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            $fullPath = $path . '/' . $filename;
            
            // Read file contents
            $fileContents = file_get_contents($file->getRealPath());
            
            // Upload file using Dropbox API v2
            $uploadResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/octet-stream',
                'Dropbox-API-Arg' => json_encode([
                    'path' => $fullPath,
                    'mode' => 'add',
                    'autorename' => true,
                    'mute' => false
                ])
            ])->withBody($fileContents, 'application/octet-stream')
              ->post('https://content.dropboxapi.com/2/files/upload');
            
            if (!$uploadResponse->successful()) {
                Log::error('Dropbox upload failed: ' . $uploadResponse->body());
                return null;
            }
            
            // Create shared link
            $shareResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post('https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings', [
                'path' => $fullPath,
                'settings' => [
                    'requested_visibility' => 'public'
                ]
            ]);
            
            if (!$shareResponse->successful()) {
                if ($shareResponse->json('error_summary', '')->contains('shared_link_already_exists')) {
                    $listLinksResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Content-Type' => 'application/json'
                    ])->post('https://api.dropboxapi.com/2/sharing/list_shared_links', [
                        'path' => $fullPath
                    ]);
                    
                    if ($listLinksResponse->successful() && isset($listLinksResponse->json('links')[0]['url'])) {
                        $url = $listLinksResponse->json('links')[0]['url'];
                    } else {
                        Log::error('Failed to get existing shared link: ' . $listLinksResponse->body());
                        return null;
                    }
                } else {
                    Log::error('Failed to create shared link: ' . $shareResponse->body());
                    return null;
                }
            } else {
                $url = $shareResponse->json('url');
            }
            
            // Convert to direct download URL
            $directUrl = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $url);
            $directUrl = str_replace('?dl=0', '', $directUrl);
            
            return $directUrl;
        } catch (\Exception $e) {
            Log::error('Dropbox upload failed: ' . $e->getMessage());
            return null;
        }
    }
}