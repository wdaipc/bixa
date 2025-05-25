<?php

namespace App\Services;

use App\Models\CloudflareConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected $apiBaseUrl = 'https://api.cloudflare.com/client/v4';
    protected $config;

    public function __construct()
    {
        $this->config = CloudflareConfig::where('is_active', true)->first();
    }

    /**
     * Check if Cloudflare is configured
     *
     * @return bool
     */
    public function isConfigured()
    {
        return !is_null($this->config) && 
               !empty($this->config->email) && 
               !empty($this->config->api_key) && 
               !empty($this->config->proxy_domain);
    }

    /**
     * Get proxy domain
     *
     * @return string|null
     */
    public function getProxyDomain()
    {
        return $this->config ? $this->config->proxy_domain : null;
    }

    /**
     * Test connection to Cloudflare API
     *
     * @return bool
     */
    public function testConnection()
    {
        try {
            if (!$this->isConfigured()) {
                return false;
            }

            $response = $this->makeRequest('GET', '/user');
            
            return $response->successful() && $response->json('success');
        } catch (\Exception $e) {
            Log::error('Cloudflare connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get zone ID for proxy domain
     *
     * @return string
     */
    protected function getZoneId()
    {
        try {
            $response = $this->makeRequest('GET', '/zones', [
                'name' => $this->config->proxy_domain,
                'status' => 'active'
            ]);

            if ($response->successful() && $response->json('success')) {
                $zones = $response->json('result');
                if (!empty($zones)) {
                    return $zones[0]['id'];
                }
            }

            throw new \Exception("Zone not found for domain: {$this->config->proxy_domain}");
        } catch (\Exception $e) {
            Log::error('Failed to get zone ID', [
                'domain' => $this->config->proxy_domain,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get zone ID by domain name
     *
     * @param string $domain
     * @return string
     */
    public function getZoneIdByDomain($domain)
    {
        try {
            if (!$this->config) {
                throw new \Exception('Cloudflare configuration not found');
            }

            $response = $this->makeRequest('GET', '/zones', [
                'name' => $domain,
                'status' => 'active'
            ]);

            if ($response->successful() && $response->json('success')) {
                $zones = $response->json('result');
                if (!empty($zones)) {
                    return $zones[0]['id'];
                }
            }

            throw new \Exception("Zone not found for domain: {$domain}");
        } catch (\Exception $e) {
            Log::error('Failed to get zone ID by domain', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate record name for proxy domain
     *
     * @param string $domain
     * @return string
     */
    protected function getRecordName($domain)
    {
        // Extract subdomain if exists
        $parts = explode('.', $domain);
        if (count($parts) > 2) {
            $subdomain = array_shift($parts); // Get first part as subdomain
            return "_acme-challenge.{$subdomain}.{$this->config->proxy_domain}";
        }
        return "_acme-challenge.{$this->config->proxy_domain}";
    }

    /**
     * Create proxy record name for domain
     *
     * @param string $domain
     * @return string
     */
    public function createProxyRecordName($domain)
    {
        // Get subdomain from original domain
        $parts = explode('.', $domain);
        $prefix = '_acme-challenge';
        
        if (count($parts) > 2) {
            // If it's a subdomain, add subdomain to proxy record
            $subdomain = array_shift($parts); // Get subdomain part
            $prefix = $prefix . '.' . $subdomain;
        }
        
        return $prefix . '.' . $this->config->proxy_domain;
    }

    /**
     * Create TXT record for ACME challenge
     *
     * @param string $name Original record name (not used, we generate our own)
     * @param string $content TXT record content
     * @param string $domain Original domain for processing
     * @return array
     */
    public function createTxtRecord($name, $content, $domain)
    {
        if (!$this->config) {
            throw new \Exception('Cloudflare configuration not found');
        }

        try {
            // Generate proper record name based on domain type
            $proxyRecordName = $this->getRecordName($domain);
            
            Log::info('Creating Cloudflare TXT record', [
                'domain' => $domain,
                'record_name' => $proxyRecordName,
                'content' => $content
            ]);

            // Get zone ID for proxy domain
            $zoneId = $this->getZoneId();

            // Create TXT record data
            $recordData = [
                'type' => 'TXT',
                'name' => $proxyRecordName,
                'content' => $content,
                'ttl' => 120 // 2 minutes for quick propagation
            ];

            $response = $this->makeRequest('POST', "/zones/{$zoneId}/dns_records", $recordData);

            if (!$response->successful() || !$response->json('success')) {
                $errorMessage = $response->json('errors.0.message') ?? 'Failed to create TXT record';
                throw new \Exception($errorMessage);
            }

            $result = $response->json('result');

            Log::info('Successfully created Cloudflare TXT record', [
                'domain' => $domain,
                'record_name' => $proxyRecordName,
                'record_id' => $result['id']
            ]);

            return [
                'id' => $result['id'],
                'name' => $result['name'], // Full FQDN from Cloudflare
                'content' => $result['content'],
                'zone_id' => $zoneId
            ];

        } catch (\Exception $e) {
            Log::error('Cloudflare Error', [
                'operation' => 'createTxtRecord',
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete TXT record
     *
     * @param string $recordId
     * @param string $zoneId
     * @return bool
     */
    public function deleteTxtRecord($recordId, $zoneId)
    {
        if (!$this->config) {
            throw new \Exception('Cloudflare configuration not found');
        }

        try {
            $response = $this->makeRequest('DELETE', "/zones/{$zoneId}/dns_records/{$recordId}");

            if ($response->successful() && $response->json('success')) {
                Log::info('Successfully deleted Cloudflare TXT record', [
                    'record_id' => $recordId,
                    'zone_id' => $zoneId
                ]);
                return true;
            }

            $errorMessage = $response->json('errors.0.message') ?? 'Failed to delete TXT record';
            throw new \Exception($errorMessage);

        } catch (\Exception $e) {
            Log::error('Failed to delete TXT record', [
                'recordId' => $recordId,
                'zoneId' => $zoneId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Make request to Cloudflare API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Http\Client\Response
     */
    protected function makeRequest($method, $endpoint, $data = [])
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Cloudflare not configured');
        }

        $url = $this->apiBaseUrl . $endpoint;

        $headers = [
            'X-Auth-Email' => $this->config->email,
            'X-Auth-Key' => $this->config->api_key,
            'Content-Type' => 'application/json',
        ];

        Log::debug('Making Cloudflare API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'email' => $this->config->email
        ]);

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
     * List all zones
     *
     * @return array
     */
    public function listZones()
    {
        try {
            $response = $this->makeRequest('GET', '/zones');
            
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
     * Get zone details
     *
     * @param string $zoneId
     * @return array
     */
    public function getZone($zoneId)
    {
        try {
            $response = $this->makeRequest('GET', "/zones/{$zoneId}");
            
            if ($response->successful() && $response->json('success')) {
                return $response->json('result');
            }

            throw new \Exception($response->json('errors.0.message') ?? 'Failed to get zone');
        } catch (\Exception $e) {
            Log::error('Failed to get zone', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * List DNS records for a zone
     *
     * @param string $zoneId
     * @param string $type Record type filter
     * @param string $name Record name filter
     * @return array
     */
    public function listDnsRecords($zoneId, $type = null, $name = null)
    {
        try {
            $params = [];
            if ($type) $params['type'] = $type;
            if ($name) $params['name'] = $name;

            $response = $this->makeRequest('GET', "/zones/{$zoneId}/dns_records", $params);
            
            if ($response->successful() && $response->json('success')) {
                return $response->json('result');
            }

            throw new \Exception($response->json('errors.0.message') ?? 'Failed to list DNS records');
        } catch (\Exception $e) {
            Log::error('Failed to list DNS records', [
                'zone_id' => $zoneId,
                'type' => $type,
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}