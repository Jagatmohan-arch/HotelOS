<?php
/**
 * HotelOS - Cashfree Payment Handler
 * 
 * Integrates with Cashfree Payment Gateway for subscriptions
 * Docs: https://docs.cashfree.com/reference/pg-new-apis-endpoint
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class CashfreeHandler
{
    private Database $db;
    private string $appId;
    private string $secretKey;
    private string $apiUrl;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Load from environment or config
        $this->appId = $_ENV['CASHFREE_APP_ID'] ?? '';
        $this->secretKey = $_ENV['CASHFREE_SECRET_KEY'] ?? '';
        
        // Use sandbox for testing, production for live
        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
        $this->apiUrl = $isProduction 
            ? 'https://api.cashfree.com/pg' 
            : 'https://sandbox.cashfree.com/pg';
    }
    
    /**
     * Create a payment order for subscription
     * 
     * @param int $tenantId Tenant purchasing subscription
     * @param string $planSlug Plan being purchased
     * @param float $amount Amount to charge
     * @return array Order details or error
     */
    public function createOrder(int $tenantId, string $planSlug, float $amount): array
    {
        // Get tenant details
        $tenant = $this->db->queryOne(
            "SELECT * FROM tenants WHERE id = :id",
            ['id' => $tenantId],
            enforceTenant: false
        );
        
        if (!$tenant) {
            return ['success' => false, 'error' => 'Tenant not found'];
        }
        
        // Generate unique order ID
        $orderId = 'ORDER_' . $tenantId . '_' . time();
        
        // Create order payload
        $orderData = [
            'order_id' => $orderId,
            'order_amount' => $amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => 'TENANT_' . $tenantId,
                'customer_name' => $tenant['name'],
                'customer_email' => $tenant['email'],
                'customer_phone' => $tenant['phone']
            ],
            'order_meta' => [
                'return_url' => $this->getReturnUrl(),
                'notify_url' => $this->getWebhookUrl()
            ],
            'order_note' => "HotelOS {$planSlug} subscription"
        ];
        
        // Call Cashfree API
        $response = $this->makeApiRequest('/orders', 'POST', $orderData);
        
        if (!$response['success']) {
            return $response;
        }
        
        // Save order to database
        $this->db->execute(
            "INSERT INTO subscription_transactions 
             (tenant_id, amount, type, status, gateway_order_id, metadata, created_at)
             VALUES (:tenant_id, :amount, 'subscription', 'pending', :order_id, :metadata, NOW())",
            [
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'order_id' => $orderId,
                'metadata' => json_encode(['plan' => $planSlug])
            ],
            enforceTenant: false
        );
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'payment_session_id' => $response['data']['payment_session_id'] ?? null,
            'order_token' => $response['data']['order_token'] ?? null
        ];
    }
    
    /**
     * Verify payment signature (webhook/callback)
     * 
     * @param array $payload Cashfree webhook payload
     * @return bool True if signature is valid
     */
    public function verifySignature(array $payload, string $signature): bool
    {
        // Cashfree signature format: timestamp.rawBody
        $timestamp = $payload['timestamp'] ?? '';
        $rawBody = json_encode($payload);
        
        $computedSignature = base64_encode(hash_hmac(
            'sha256',
            $timestamp . '.' . $rawBody,
            $this->secretKey,
            true
        ));
        
        return hash_equals($computedSignature, $signature);
    }
    
    /**
     * Handle payment webhook from Cashfree
     * 
     * @param array $payload Webhook data
     * @return array Processing result
     */
    public function handleWebhook(array $payload): array
    {
        $orderId = $payload['order_id'] ?? null;
        $status = $payload['order_status'] ?? null;
        
        if (!$orderId) {
            return ['success' => false, 'error' => 'Missing order_id'];
        }
        
        // Get transaction from database
        $transaction = $this->db->queryOne(
            "SELECT * FROM subscription_transactions WHERE gateway_order_id = :order_id",
            ['order_id' => $orderId],
            enforceTenant: false
        );
        
        if (!$transaction) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }
        
        // Update transaction status
        $newStatus = $status === 'PAID' ? 'success' : 'failed';
        
        $this->db->execute(
            "UPDATE subscription_transactions 
             SET status = :status, 
                 gateway_transaction_id = :txn_id,
                 updated_at = NOW()
             WHERE id = :id",
            [
                'id' => $transaction['id'],
                'status' => $newStatus,
                'txn_id' => $payload['cf_payment_id'] ?? null
            ],
            enforceTenant: false
        );
        
        // If payment successful, activate subscription
        if ($newStatus === 'success') {
            $metadata = json_decode($transaction['metadata'], true);
            $planSlug = $metadata['plan'] ?? 'starter';
            
            $subscriptionHandler = new SubscriptionHandler();
            $subscriptionHandler->upgradePlan($transaction['tenant_id'], $planSlug);
        }
        
        return ['success' => true, 'status' => $newStatus];
    }
    
    /**
     * Make API request to Cashfree
     */
    private function makeApiRequest(string $endpoint, string $method = 'GET', ?array $data = null): array
    {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: ' . $this->appId,
            'x-client-secret: ' . $this->secretKey
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'API request failed',
                'http_code' => $httpCode,
                'response' => $response
            ];
        }
        
        return [
            'success' => true,
            'data' => json_decode($response, true)
        ];
    }
    
    /**
     * Get return URL after payment
     */
    private function getReturnUrl(): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'https://hotelos.needkit.in';
        return $baseUrl . '/subscription/payment-success';
    }
    
    /**
     * Get webhook URL for payment notifications
     */
    private function getWebhookUrl(): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'https://hotelos.needkit.in';
        return $baseUrl . '/subscription/webhook';
    }
}
