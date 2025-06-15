<?php

namespace App\Http\Controllers\User;

use Afosto\Acme\Client;
use Afosto\Acme\Helper;
use Afosto\Acme\Data\Certificate;
use Afosto\Acme\Data\Order;
use Afosto\Acme\Data\Authorization;
use GuzzleHttp\Client as HttpClient;

class ExtendedAcmeClient extends Client 
{
    /**
     * Override parent getAccount method to handle missing fields
     */
    public function getAccount(): \Afosto\Acme\Data\Account
    {
        try {
            $response = $this->request(
                $this->getUrl(self::DIRECTORY_NEW_ACCOUNT),
                $this->signPayloadJWK(
                    [
                        'onlyReturnExisting' => true,
                    ],
                    $this->getUrl(self::DIRECTORY_NEW_ACCOUNT)
                )
            );

            $data = json_decode((string)$response->getBody(), true);
            
            // Log the response data for debugging
            \Log::debug('ACME Account response', ['response_data' => $data]);
            
            // Check if response is valid
            if (!is_array($data)) {
                throw new \Exception('Invalid response from ACME server');
            }

            $accountURL = $response->getHeaderLine('Location');
            
            // Handle missing contact field with fallback
            $contact = $data['contact'] ?? ['mailto:' . $this->getOption('username')];
            
            // Handle missing createdAt field with multiple fallbacks
            $createdAt = $data['createdAt'] ?? $data['created'] ?? date('c');
            $date = (new \DateTime())->setTimestamp(strtotime($createdAt));
            
            // Handle missing status field
            $status = $data['status'] ?? 'valid';
            
            return new \Afosto\Acme\Data\Account($contact, $date, ($status == 'valid'), $accountURL);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get ACME account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getCertificate(Order $order): Certificate
    {
        try {
            $privateKey = Helper::getNewKey($this->getOption('key_length', 2048));
            $csr = Helper::getCsr($order->getDomains(), $privateKey);
            $der = Helper::toDer($csr);

            \Log::debug('Finalizing order...', [
                'order_id' => $order->getId(),
                'domains' => $order->getDomains()
            ]);

            // Bước 1: Finalize order với CSR
            $response = $this->request(
                $order->getFinalizeURL(),
                $this->signPayloadKid(
                    ['csr' => Helper::toSafeString($der)],
                    $order->getFinalizeURL()
                )
            );

            $maxAttempts = 10;
            $certUrl = null;
            
            do {
                sleep(3); 
                $currentOrder = $this->getOrder($order->getId());
                
                \Log::debug('Checking order status', [
                    'status' => $currentOrder->getStatus(),
                    'attempts_left' => $maxAttempts
                ]);
                
                if ($currentOrder->getStatus() === 'valid') {
                    // Lấy certificate URL từ order
                    $orderResponse = $this->request(
                        $currentOrder->getURL(),
                        $this->signPayloadKid(null, $currentOrder->getURL())
                    );
                    $orderData = json_decode((string)$orderResponse->getBody(), true);
                    
                    if (isset($orderData['certificate'])) {
                        $certUrl = $orderData['certificate'];
                        \Log::debug('Certificate URL obtained', ['url' => $certUrl]);
                        break;
                    }
                } elseif ($currentOrder->getStatus() === 'invalid') {
                    throw new \Exception('Order became invalid. Please check your DNS records and try again.');
                }
                $maxAttempts--;
            } while ($maxAttempts > 0);

            if (!$certUrl) {
                throw new \Exception('Could not obtain certificate URL after multiple attempts. Order status: ' . ($currentOrder->getStatus() ?? 'unknown'));
            }

            $certificateResponse = $this->request(
                $certUrl,
                $this->signPayloadKid(null, $certUrl)
            );
            
            $chain = preg_replace('/^[ \t]*[\r\n]+/m', '', (string)$certificateResponse->getBody());
            
            \Log::debug('Certificate chain obtained', [
                'chain_length' => strlen($chain),
                'contains_begin_cert' => strpos($chain, '-----BEGIN CERTIFICATE-----') !== false
            ]);

            if (empty($chain) || strpos($chain, '-----BEGIN CERTIFICATE-----') === false) {
                throw new \Exception('Invalid certificate chain received from ACME server');
            }

            return new Certificate($privateKey, $csr, $chain);
            
        } catch (\Exception $e) {
            \Log::error('Certificate generation failed', [
                'order_id' => $order->getId() ?? 'unknown',
                'domains' => $order->getDomains() ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function selfDNSTest(Authorization $authorization, $maxAttempts): bool  
    {
        try {
            $txtRecord = $authorization->getTxtRecord();
            $recordName = $txtRecord->getName();
            $expectedValue = $txtRecord->getValue();

            \Log::debug('Performing DNS self-test', [
                'record_name' => $recordName,
                'expected_value' => $expectedValue,
                'max_attempts' => $maxAttempts
            ]);

            $attempts = 0;
            do {
                $attempts++;
                
                // Get DNS TXT records with error handling
                $records = @dns_get_record($recordName, DNS_TXT);
                
                if ($records && is_array($records)) {
                    foreach ($records as $record) {
                        if (isset($record['txt']) && $record['txt'] === $expectedValue) {
                            \Log::debug('DNS self-test passed', [
                                'record_name' => $recordName,
                                'attempt' => $attempts
                            ]);
                            return true;
                        }
                    }
                }

                if ($attempts < $maxAttempts) {
                    sleep(5); // Wait before retry
                }

            } while ($attempts < $maxAttempts);

            \Log::warning('DNS self-test failed', [
                'record_name' => $recordName,
                'expected_value' => $expectedValue,
                'attempts' => $attempts,
                'found_records' => $records ?? []
            ]);

            return false;

        } catch (\Exception $e) {
            \Log::error('DNS self-test error', [
                'error' => $e->getMessage(),
                'record_name' => $recordName ?? 'unknown'
            ]);
            return false;
        }
    }

    public function checkDNSDetails(Authorization $authorization): array
    {
        try {
            $txtRecord = $authorization->getTxtRecord();
            $recordName = $txtRecord->getName();
            $expectedValue = $txtRecord->getValue();

            // Use @ to suppress warnings for DNS lookup failures
            $records = @dns_get_record($recordName, DNS_TXT);
            $foundRecords = [];
            
            if ($records && is_array($records)) {
                foreach ($records as $record) {
                    if (isset($record['txt'])) {
                        $foundRecords[] = $record['txt'];
                    }
                }
            }

            $success = in_array($expectedValue, $foundRecords);

            \Log::debug('DNS details check', [
                'record_name' => $recordName,
                'expected' => $expectedValue,
                'found' => $foundRecords,
                'success' => $success
            ]);

            return [
                'success' => $success,
                'message' => $success ? 
                    'DNS record found and matched' : 
                    'DNS record not matched or not found',
                'expected' => $expectedValue,
                'found' => $foundRecords,
                'record_name' => $recordName
            ];

        } catch (\Exception $e) {
            \Log::error('DNS details check error', [
                'error' => $e->getMessage(),
                'record_name' => $recordName ?? 'unknown'
            ]);

            return [
                'success' => false,
                'message' => 'Error checking DNS: ' . $e->getMessage(),
                'expected' => $expectedValue ?? 'unknown',
                'found' => [],
                'record_name' => $recordName ?? 'unknown'
            ];
        }
    }
}