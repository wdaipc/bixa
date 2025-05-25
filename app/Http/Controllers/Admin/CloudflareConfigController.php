<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudflareConfig;
use App\Services\CloudflareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CloudflareConfigController extends Controller
{
    protected $cloudflareService;
    protected $apiBaseUrl = 'https://api.cloudflare.com/client/v4';

    public function __construct(CloudflareService $cloudflareService)
    {
        $this->cloudflareService = $cloudflareService;
    }

    public function index()
    {
        $config = CloudflareConfig::where('is_active', true)->first();
        return view('admin.cloudflare.index', compact('config'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'api_key' => 'required|string',
            'proxy_domain' => 'required|string',
        ]);

        try {
            // Deactivate old configs
            CloudflareConfig::query()->update(['is_active' => false]);

            // Create new config
            CloudflareConfig::create([
                'email' => $request->email,
                'api_key' => $request->api_key,
                'proxy_domain' => $request->proxy_domain,
                'is_active' => true,
            ]);

            Log::info('Cloudflare configuration updated', [
                'email' => $request->email,
                'proxy_domain' => $request->proxy_domain
            ]);

            return redirect()
                ->route('admin.cloudflare.index')
                ->with('success', 'Cloudflare configuration updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update Cloudflare config', [
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput(request()->except(['api_key']))
                ->with('error', 'Failed to save configuration');
        }
    }

    public function test()
    {
        try {
            $result = $this->cloudflareService->testConnection();
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection test successful!'
                ]);
            }

            throw new \Exception('Connection test failed');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function testSDK()
    {
        try {
            $config = CloudflareConfig::where('is_active', true)->first();
            
            if (!$config) {
                throw new \Exception('No active configuration found');
            }

            // Log the attempt
            Log::info('Testing Cloudflare API connection', [
                'email' => $config->email
            ]);

            // Make direct API call to Cloudflare
            $response = $this->makeCloudflareRequest('GET', '/user', [], $config);

            // Log response
            Log::info('API Response received', [
                'response' => $response->json()
            ]);

            // Check if the response is successful
            if ($response->successful() && $response->json('success')) {
                $userData = $response->json('result');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Connection test successful!',
                    'data' => [
                        'user_id' => $userData['id'] ?? null,
                        'email' => $userData['email'] ?? null,
                        'first_name' => $userData['first_name'] ?? null,
                        'last_name' => $userData['last_name'] ?? null,
                    ]
                ]);
            }

            // Handle API errors
            $errorMessage = $response->json('errors.0.message') ?? 'Unknown API error';
            throw new \Exception($errorMessage);

        } catch (\Exception $e) {
            // Log error details
            Log::error('Cloudflare API test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make a request to Cloudflare API
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint (e.g., '/user', '/zones')
     * @param array $data Request data for POST/PUT requests
     * @param CloudflareConfig $config Cloudflare configuration
     * @return \Illuminate\Http\Client\Response
     */
    protected function makeCloudflareRequest($method, $endpoint, $data = [], $config = null)
    {
        if (!$config) {
            $config = CloudflareConfig::where('is_active', true)->first();
            
            if (!$config) {
                throw new \Exception('No active Cloudflare configuration found');
            }
        }

        $url = $this->apiBaseUrl . $endpoint;

        // Prepare headers
        $headers = [
            'X-Auth-Email' => $config->email,
            'X-Auth-Key' => $config->api_key,
            'Content-Type' => 'application/json',
        ];

        // Log the request
        Log::info('Making Cloudflare API request', [
            'method' => $method,
            'url' => $url,
            'email' => $config->email
        ]);

        // Make the request
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->retry(3, 1000);

        switch (strtoupper($method)) {
            case 'GET':
                return $response->get($url, $data);
            case 'POST':
                return $response->post($url, $data);
            case 'PUT':
                return $response->put($url, $data);
            case 'DELETE':
                return $response->delete($url, $data);
            default:
                throw new \Exception('Unsupported HTTP method: ' . $method);
        }
    }

    /**
     * Get user details from Cloudflare API
     *
     * @return array
     */
    public function getUserDetails()
    {
        try {
            $response = $this->makeCloudflareRequest('GET', '/user');
            
            if ($response->successful() && $response->json('success')) {
                return $response->json('result');
            }

            throw new \Exception($response->json('errors.0.message') ?? 'Failed to get user details');
        } catch (\Exception $e) {
            Log::error('Failed to get user details', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * List zones from Cloudflare API
     *
     * @param array $params Query parameters
     * @return array
     */
    public function listZones($params = [])
    {
        try {
            $response = $this->makeCloudflareRequest('GET', '/zones', $params);
            
            if ($response->successful() && $response->json('success')) {
                return $response->json('result');
            }

            throw new \Exception($response->json('errors.0.message') ?? 'Failed to list zones');
        } catch (\Exception $e) {
            Log::error('Failed to list zones', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get zone details by zone ID
     *
     * @param string $zoneId
     * @return array
     */
    public function getZone($zoneId)
    {
        try {
            $response = $this->makeCloudflareRequest('GET', "/zones/{$zoneId}");
            
            if ($response->successful() && $response->json('success')) {
                return $response->json('result');
            }

            throw new \Exception($response->json('errors.0.message') ?? 'Failed to get zone details');
        } catch (\Exception $e) {
            Log::error('Failed to get zone details', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create or update DNS record
     *
     * @param string $zoneId
     * @param array $recordData
     * @return array
     */
    public function createDnsRecord($zoneId, $recordData)
    {
        try {
            $response = $this->makeCloudflareRequest('POST', "/zones/{$zoneId}/dns_records", $recordData);
            
            if ($response->successful() && $response->json('success')) {
                return $response->json('result');
            }

            throw new \Exception($response->json('errors.0.message') ?? 'Failed to create DNS record');
        } catch (\Exception $e) {
            Log::error('Failed to create DNS record', [
                'zone_id' => $zoneId,
                'record_data' => $recordData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update DNS record
     *
     * @param string $zoneId
     * @param string $recordId
     * @param array $recordData
     * @return array
     */
    public function updateDnsRecord($zoneId, $recordId, $recordData)
    {
        try {
            $response = $this->makeCloudflareRequest('PUT', "/zones/{$zoneId}/dns_records/{$recordId}", $recordData);
            
            if ($response->successful() && $response->json('success')) {
                return $response->json('result');
            }

            throw new \Exception($response->json('errors.0.message') ?? 'Failed to update DNS record');
        } catch (\Exception $e) {
            Log::error('Failed to update DNS record', [
                'zone_id' => $zoneId,
                'record_id' => $recordId,
                'record_data' => $recordData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete DNS record
     *
     * @param string $zoneId
     * @param string $recordId
     * @return bool
     */
    public function deleteDnsRecord($zoneId, $recordId)
    {
        try {
            $response = $this->makeCloudflareRequest('DELETE', "/zones/{$zoneId}/dns_records/{$recordId}");
            
            if ($response->successful() && $response->json('success')) {
                return true;
            }

            throw new \Exception($response->json('errors.0.message') ?? 'Failed to delete DNS record');
        } catch (\Exception $e) {
            Log::error('Failed to delete DNS record', [
                'zone_id' => $zoneId,
                'record_id' => $recordId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}