<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\PaymentGateway;

class PayPalManualService
{
    private $paypalGateway;
    private $accessToken;
    private $tokenExpiresAt;
    
    public function __construct()
    {
        $this->paypalGateway = PaymentGateway::byName('paypal')->active()->first();
    }
    
    private function getAccessToken(): string
    {
        // Check if token is still valid
        if ($this->accessToken && $this->tokenExpiresAt && now() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }
        
        // Fetch new token
        $clientId = $this->paypalGateway->getConfigValue('client_id');
        $clientSecret = $this->paypalGateway->getConfigValue('client_secret');
        $mode = $this->paypalGateway->getConfigValue('mode', 'sandbox');
        
        $baseUrl = $mode === 'live' 
            ? 'https://api.paypal.com' 
            : 'https://api.sandbox.paypal.com';
        
        try {
            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en_US',
                ])
                ->asForm()
                ->post($baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);
            
            if (!$response->successful()) {
                throw new Exception('Failed to get PayPal access token: ' . $response->body());
            }
            
            $tokenData = $response->json();
            $this->accessToken = $tokenData['access_token'];
            $this->tokenExpiresAt = now()->addSeconds($tokenData['expires_in'] - 60); // 60s buffer
            
            Log::info('PayPal access token obtained', [
                'expires_in' => $tokenData['expires_in'],
                'token_type' => $tokenData['token_type']
            ]);
            
            return $this->accessToken;
            
        } catch (Exception $e) {
            Log::error('PayPal token fetch failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public function createOrder(float $amount, int $orderId, string $currency = 'USD'): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $mode = $this->paypalGateway->getConfigValue('mode', 'sandbox');
            
            $baseUrl = $mode === 'live' 
                ? 'https://api.paypal.com' 
                : 'https://api.sandbox.paypal.com';
            
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => (string) $orderId,
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => number_format($amount, 2, '.', '')
                        ],
                        'description' => "Order #{$orderId}"
                    ]
                ],
                'application_context' => [
                    'return_url' => config('app.url') . '/payment/success',
                    'cancel_url' => config('app.url') . '/payment/cancel'
                ]
            ];
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
                'Prefer' => 'return=representation',
                'PayPal-Request-Id' => uniqid('PAYPAL_REQ_')
            ])->post($baseUrl . '/v2/checkout/orders', $orderData);
            
            if (!$response->successful()) {
                throw new Exception('PayPal API error: ' . $response->body());
            }
            
            $orderResponse = $response->json();
            
            Log::info('PayPal Order created via manual service', [
                'order_id' => $orderId,
                'paypal_order_id' => $orderResponse['id'],
                'status' => $orderResponse['status']
            ]);
            
            // Find approval URL
            $approvalUrl = null;
            foreach ($orderResponse['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }
            
            return [
                'paypal_order_id' => $orderResponse['id'],
                'approval_url' => $approvalUrl,
                'status' => $orderResponse['status'],
                'links' => $orderResponse['links']
            ];
            
        } catch (Exception $e) {
            Log::error('PayPal manual order creation failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            throw $e;
        }
    }
}