# Cashfree Payment Gateway Setup Guide

## Step 1: Create Cashfree Account

1. Go to [https://www.cashfree.com/](https://www.cashfree.com/)
2. Sign up for merchant account
3. Complete KYC verification

## Step 2: Get API Credentials

1. Login to Cashfree Dashboard
2. Go to **Developers → API Keys**
3. Generate credentials for:
   - **Sandbox** (for testing)
   - **Production** (for live)

You'll get:
- **App ID** (Client ID)
- **Secret Key**

## Step 3: Configure HotelOS

Add to your `.env` file:

```bash
# Cashfree Payment Gateway
CASHFREE_APP_ID=your_app_id_here
CASHFREE_SECRET_KEY=your_secret_key_here

# Use 'production' for live, 'development' for testing
APP_ENV=development
APP_URL=https://hotelos.needkit.in
```

## Step 4: Set up Webhook

In Cashfree Dashboard:
1. Go to **Developers → Webhooks**
2. Add webhook URL: `https://hotelos.needkit.in/subscription/webhook`
3. Select events:
   - **Payment Success**
   - **Payment Failed**

## Step 5: Test Payment Flow

### Using Sandbox (Test Mode)

**Test Card Details**:
- Card Number: `4111 1111 1111 1111`
- CVV: `123`
- Expiry: Any future date
- OTP: `123456`

**Test UPI**:
- UPI ID: `success@upi`

**Test Flow**:
1. Go to `/subscription/plans`
2. Select a plan
3. Click "Select Plan"
4. On checkout page, click "Pay"
5. Use test card details
6. Verify subscription activates

## Step 6: Go Live

1. Complete KYC in Cashfree dashboard
2. Get account approved
3. Update `.env` with production credentials:
   ```bash
   APP_ENV=production
   CASHFREE_APP_ID=production_app_id
   CASHFREE_SECRET_KEY=production_secret_key
   ```

## API Endpoints Created

| Endpoint | Purpose |
|----------|---------|
| `/subscription/plans` | View pricing plans |
| `/subscription/checkout?plan=starter` | Checkout page |
| `/subscription/webhook` | Payment webhook (POST) |
| `/subscription/payment-success` | Return URL after payment |

## Testing Checklist

- [ ] Sandbox credentials configured
- [ ] Can view plans page
- [ ] Can initiate checkout
- [ ] Test card payment works
- [ ] Webhook receives payment notification
- [ ] Subscription activates after payment
- [ ] Trial expires and redirects to billing

## Troubleshooting

### Payment order creation fails
- Check API credentials in `.env`
- Verify Cashfree account is active
- Check error logs

### Webhook not receiving events
- Verify webhook URL in Cashfree dashboard
- Check URL is publicly accessible (not localhost)
- Test webhook with Cashfree's webhook tester

### Payment successful but subscription not activated
- Check `subscription_transactions` table
- Verify webhook signature validation
- Check PHP error logs

## Security Notes

- Never commit `.env` file to git
- Use HTTPS in production
- Verify webhook signatures
- Store secret keys securely

## Fees

**Cashfree Pricing** (as of 2024):
- Domestic cards: 1.75% + GST
- UPI: 0% (free for first ₹1 crore/month)
- International cards: 3% + GST

**Settlement**: T+1 (next business day)

---

**Next Step**: Test the payment flow using sandbox credentials!
