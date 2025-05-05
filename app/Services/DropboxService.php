<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class DropboxService
{
    protected $accessToken;
    protected $refreshToken;
    protected $clientId;
    protected $clientSecret;
    
    public function __construct()
    {
        $this->refreshToken = config('services.dropbox.refresh_token');
        $this->clientId = config('services.dropbox.client_id');
        $this->clientSecret = config('services.dropbox.client_secret');
        $this->accessToken = $this->getValidAccessToken();
    }
    
    /**
     * Get or refresh access token
     */
    protected function getValidAccessToken()
    {
        return Cache::remember('dropbox_access_token', 14000, function () {
            return $this->refreshAccessToken();
        });
    }
    
    /**
     * Refresh the access token
     */
    protected function refreshAccessToken()
    {
        try {
            $response = Http::asForm()->post('https://api.dropbox.com/oauth2/token', [
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'];
            }
            
            Log::error('Dropbox token refresh failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new \Exception('Failed to refresh token');
            
        } catch (\Exception $e) {
            Log::error('Dropbox token refresh error: '.$e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Upload file to Dropbox with automatic token refresh
     */
    public function uploadFile(UploadedFile $file, string $path = '/place-images')
    {
        try {
            // Generate unique filename
            $filename = Str::random(20).'.'.$file->getClientOriginalExtension();
            $fullPath = rtrim($path, '/').'/'.$filename;
            
            // Prepare file contents
            $fileContents = file_get_contents($file->getRealPath());
            
            // First upload attempt
            $uploadResponse = $this->attemptUpload($fullPath, $fileContents);
            
            // If unauthorized, refresh token and retry
            if ($uploadResponse->status() === 401) {
                Cache::forget('dropbox_access_token');
                $this->accessToken = $this->refreshAccessToken();
                $uploadResponse = $this->attemptUpload($fullPath, $fileContents);
            }
            
            if (!$uploadResponse->successful()) {
                Log::error('Dropbox upload failed', [
                    'status' => $uploadResponse->status(),
                    'response' => $uploadResponse->body()
                ]);
                return null;
            }
            
            // Create shareable link
            return $this->createShareableLink($fullPath);
            
        } catch (\Exception $e) {
            Log::error('Dropbox upload error: '.$e->getMessage());
            return null;
        }
    }
    
    /**
     * Attempt file upload
     */
    protected function attemptUpload(string $path, string $contents)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/octet-stream',
            'Dropbox-API-Arg' => json_encode([
                'path' => $path,
                'mode' => 'add',
                'autorename' => true,
                'mute' => false
            ])
        ])->withBody($contents, 'application/octet-stream')
          ->post('https://content.dropboxapi.com/2/files/upload');
    }
    
    /**
     * Create shareable link
     */
    protected function createShareableLink(string $path)
    {
        $shareResponse = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json'
        ])->post('https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings', [
            'path' => $path,
            'settings' => ['requested_visibility' => 'public']
        ]);
        
        // Handle existing links
        if (!$shareResponse->successful()) {
            if (str_contains($shareResponse->json('error_summary', ''), 'shared_link_already_exists')) {
                $listResponse = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->accessToken,
                    'Content-Type' => 'application/json'
                ])->post('https://api.dropboxapi.com/2/sharing/list_shared_links', [
                    'path' => $path
                ]);
                
                if ($listResponse->successful()) {
                    return $this->convertToDirectLink($listResponse->json('links')[0]['url']);
                }
            }
            return null;
        }
        
        return $this->convertToDirectLink($shareResponse->json('url'));
    }
    
    /**
     * Convert to direct download link
     */
    protected function convertToDirectLink(string $url)
    {
        return str_replace(
            ['www.dropbox.com', '?dl=0'],
            ['dl.dropboxusercontent.com', ''],
            $url
        );
    }
}