# HotelOS Cashfree Payment Gateway Setup

## Current Status: **SANDBOX ONLY**

> ⚠️ **Important:** Cashfree integration is currently configured for sandbox/testing only.
> Production credentials require merchant verification with Cashfree.

---

## Overview

HotelOS uses Cashfree for:
- Subscription payments
- Plan upgrades
- Auto-debit (future)

---

## Configuration

Add these to your `.env` file:

```env
# Cashfree API Configuration
CASHFREE_APP_ID=your_app_id_here
CASHFREE_SECRET_KEY=your_secret_key_here
CASHFREE_ENVIRONMENT=sandbox
```

### Environment Values:
- `sandbox` - For testing (use test cards)
- `production` - For live transactions (requires KYC)

---

## Getting Sandbox Credentials

1. Go to [Cashfree Merchant Dashboard](https://merchant.cashfree.com/)
2. Register for a **Sandbox Account**
3. Navigate to **Developers > API Keys**
4. Copy **App ID** and **Secret Key**

---

## Webhook Setup

Configure webhook URL in Cashfree dashboard:

**Webhook URL:** `https://your-domain.com/subscription/webhook`

**Events to enable:**
- `PAYMENT_SUCCESS`
- `PAYMENT_FAILED`
- `SUBSCRIPTION_ACTIVATED`
- `SUBSCRIPTION_CANCELLED`

---

## Test Cards (Sandbox)

| Card Number | Expiry | CVV | Result |
|-------------|--------|-----|--------|
| 4111111111111111 | Any future | Any | Success |
| 4012888888881881 | Any future | Any | Declined |

---

## Production Checklist

Before going live:

- [ ] Complete Cashfree KYC verification
- [ ] Submit bank account details
- [ ] Update `.env` with production credentials
- [ ] Change `CASHFREE_ENVIRONMENT=production`
- [ ] Test with real ₹1 transaction
- [ ] Monitor webhook deliveries

---

## Handler Location

**File:** `handlers/CashfreeHandler.php`

Key methods:
- `createOrder()` - Creates payment order
- `verifyPayment()` - Verifies payment signature
- `handleWebhook()` - Processes Cashfree webhooks

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Payment fails immediately | Check API credentials in .env |
| Webhook not received | Verify webhook URL is publicly accessible |
| Signature mismatch | Check secret key matches dashboard |
| Order creation fails | Check Cashfree account status |
