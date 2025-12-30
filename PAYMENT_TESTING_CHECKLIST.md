# Cashfree Payment Gateway - Live Testing Checklist

**Status:** Integration code ready, needs live testing  
**File:** `handlers/CashfreeHandler.php`

---

## 🔧 Pre-Testing Setup

### 1. Get Live Credentials
- [ ] Login to Cashfree Dashboard
- [ ] Navigate to **API Credentials** section
- [ ] Copy **Live App ID**
- [ ] Copy **Live Secret Key**
- [ ] Save to `.env`:
  ```ini
  CASHFREE_APP_ID=your_live_app_id
  CASHFREE_SECRET_KEY=your_live_secret_key
  CASHFREE_MODE=live
  ```

### 2. Configure Webhook URL
- [ ] In Cashfree Dashboard, set webhook URL:
  ```
  https://hotelos.needkit.in/api/cashfree/webhook
  ```
- [ ] Generate webhook secret
- [ ] Add to `.env`:
  ```ini
  CASHFREE_WEBHOOK_SECRET=your_webhook_secret
  ```

---

## ✅ Testing Checklist

### Test 1: Subscription Payment (₹999)
**Scenario:** User upgrades from trial to Starter plan

1. [ ] Login as owner with expired trial
2. [ ] Go to `/subscription/plans`
3. [ ] Click "Choose Starter" (₹999/month)
4. [ ] Verify redirect to Cashfree payment page
5. [ ] Complete payment with **test card**:
   - Card: 4111 1111 1111 1111
   - CVV: 123
   - Expiry: Any future date
6. [ ] Verify redirect back to HotelOS
7. [ ] Check if subscription status = "active"
8. [ ] Check `subscription_transactions` table for record

**Expected Result:**
- ✅ Payment success
- ✅ `tenants.billing_status` = 'active'
- ✅ `tenants.next_billing_date` = +1 month
- ✅ Transaction recorded

---

### Test 2: Webhook Verification
**Scenario:** Cashfree sends payment confirmation

1. [ ] Trigger payment (Test 1)
2. [ ] Check webhook endpoint receives POST
3. [ ] Verify signature validation works
4. [ ] Check database update happens
5. [ ] Verify email notification sent (if configured)

**Check Logs:**
```bash
tail -f /home/uplfveim/hotelos.needkit.in/logs/php_errors.log
```

**Expected Result:**
- ✅ Webhook received
- ✅ Signature validated
- ✅ Database updated
- ✅ No errors in logs

---

### Test 3: Failed Payment Handling
**Scenario:** User cancels payment or card declines

1. [ ] Login as owner
2. [ ] Attempt subscription upgrade
3. [ ] On Cashfree page, click "Cancel"
4. [ ] Verify redirect back to HotelOS
5. [ ] Check error message displayed
6. [ ] Verify `billing_status` remains unchanged

**Expected Result:**
- ✅ User redirected back
- ✅ Error message shown
- ✅ No partial database update
- ✅ User can retry

---

### Test 4: Recurring Billing (Simulation)
**Scenario:** Monthly auto-payment

**Note:** This requires waiting 1 month OR manually triggering via Cashfree dashboard

1. [ ] Set up subscription with `next_billing_date` = tomorrow
2. [ ] Wait for Cashfree to auto-debit
3. [ ] Verify webhook received
4. [ ] Check `subscription_transactions` table
5. [ ] Verify `next_billing_date` updated to +1 month

**Or use Cashfree test mode to simulate:**
- [ ] Dashboard → Test Subscriptions → Trigger payment

---

### Test 5: Subscription Cancellation
**Scenario:** User downgrades or cancels

1. [ ] Login as active subscriber
2. [ ] Go to `/subscription/cancel`
3. [ ] Confirm cancellation
4. [ ] Verify `billing_status` = 'cancelled'
5. [ ] Check if user still has access until current period ends
6. [ ] Verify auto-renewal stopped

---

### Test 6: Edge Cases

**6a: Expired Card**
- [ ] Use card with past expiry
- [ ] Verify proper error handling

**6b: Insufficient Funds**
- [ ] Use test card for declined payment
- [ ] Verify retry option shown

**6c: Network Timeout**
- [ ] Simulate slow network
- [ ] Verify timeout handling
- [ ] Check if duplicate payments prevented

**6d: Webhook Duplicate**
- [ ] Send same webhook twice
- [ ] Verify idempotency (no duplicate transaction)

---

## 🔍 Verification Points

### Database Checks
```sql
-- Check subscription transaction
SELECT * FROM subscription_transactions 
WHERE tenant_id = 1 
ORDER BY created_at DESC LIMIT 5;

-- Check billing status
SELECT id, name, billing_status, next_billing_date, trial_ends_at 
FROM tenants WHERE id = 1;

-- Check payment gateway logs
SELECT * FROM audit_logs 
WHERE entity_type = 'payment' 
ORDER BY created_at DESC LIMIT 10;
```

### Log Checks
- [ ] No PHP errors in `logs/php_errors.log`
- [ ] Webhook logs in audit
- [ ] Payment success/failure logged

### Email Notifications (if configured)
- [ ] Payment success email
- [ ] Payment failure email
- [ ] Subscription renewal reminder

---

## 🚨 Common Issues & Fixes

### Issue: Webhook not received
**Fix:**
- Check if webhook URL is publicly accessible
- Verify SSL certificate valid
- Check firewall/server blocks

### Issue: Signature mismatch
**Fix:**
- Verify `CASHFREE_WEBHOOK_SECRET` matches dashboard
- Check if webhook payload modified en route

### Issue: Payment success but DB not updated
**Fix:**
- Check webhook endpoint code
- Verify database connection in webhook handler
- Check for PHP fatal errors

---

## 📊 Success Criteria

**All tests must pass:**
- ✅ Sandbox payment works
- ✅ **Live payment works** ← MOST IMPORTANT
- ✅ Webhooks received correctly
- ✅ Database updates accurate
- ✅ Failed payments handled gracefully
- ✅ No security vulnerabilities
- ✅ Idempotency ensured

---

## 🎯 Production Readiness

### Before Going Live:
- [ ] All test cases passed
- [ ] Webhook signature validation working
- [ ] Error handling tested
- [ ] Logs monitoring set up
- [ ] Refund process documented
- [ ] Customer support trained

### After Going Live:
- [ ] Monitor first 10 transactions closely
- [ ] Check webhook delivery rate
- [ ] Review error logs daily (first week)
- [ ] Set up uptime monitoring for webhook endpoint

---

## 📞 Support

**Cashfree Support:** support@cashfree.com  
**Dashboard:** https://merchant.cashfree.com/  
**Docs:** https://docs.cashfree.com/  

**HotelOS Code:** `handlers/CashfreeHandler.php`

---

**Tester:** _______________  
**Date:** _______________  
**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________
