# HotelOS Phase A - Manual Test Checklist

**Date**: 2026-01-02  
**Purpose**: Verify system stability before commercial release

---

## Pre-Test Requirements

- [ ] Database is accessible
- [ ] Application loads at login page
- [ ] `.env` file configured with database credentials

---

## Test 1: Authentication & RBAC

### 1.1 Owner Login
- [ ] Login with `owner@grandpalace.com` / `Demo@123`
- [ ] Dashboard loads successfully
- [ ] "Engine" menu item visible (Owner-only)
- [ ] Can access `/engine` page

### 1.2 Manager Login
- [ ] Login with `manager@grandpalace.com` / `Demo@123`
- [ ] Dashboard loads (limited view)
- [ ] "Engine" menu NOT visible
- [ ] Cannot access `/engine` (returns 403)

### 1.3 Reception Login
- [ ] Login with `reception@grandpalace.com` / `Demo@123`
- [ ] Dashboard loads
- [ ] Can access `/bookings`
- [ ] Cannot access `/admin/staff`

### 1.4 PIN Quick Login
- [ ] From login page, enter PIN `5678`
- [ ] Receptionist auto-login works

---

## Test 2: Shift Management

### 2.1 Start Shift
- [ ] Login as Receptionist
- [ ] Navigate to `/shifts`
- [ ] Enter opening cash (₹5000)
- [ ] Click "Start Shift"
- [ ] Shift status shows "OPEN"

### 2.2 Shift Logout Block
- [ ] With open shift, click "Logout"
- [ ] **Expected**: Error "Cannot logout with open shift"
- [ ] Redirect to shifts page

### 2.3 Close Shift
- [ ] Navigate to `/shifts`
- [ ] Enter closing cash
- [ ] Add notes
- [ ] Click "Close Shift"
- [ ] Variance calculated correctly

---

## Test 3: Booking Flow

### 3.1 Create Booking
- [ ] Navigate to `/bookings/create`
- [ ] Search for available rooms (today + 1 night)
- [ ] Select a room
- [ ] Enter guest (use existing or create new)
- [ ] Enter advance payment (₹500 cash)
- [ ] Confirm booking
- [ ] Booking number generated

### 3.2 Check-In
- [ ] From booking list, find new booking
- [ ] Click "Check In"
- [ ] Room status changes to "Occupied"
- [ ] Booking status changes to "Checked In"

### 3.3 Payment Recording
- [ ] Open booking details
- [ ] Record additional payment (₹1000 UPI)
- [ ] Payment appears in transactions list

### 3.4 Check-Out
- [ ] Navigate to `/bookings/{id}/checkout`
- [ ] Enter extra charges (₹200 minibar)
- [ ] Review GST calculation (12% or 18%)
- [ ] Enter final payment
- [ ] Complete checkout
- [ ] Invoice generated

---

## Test 4: Invoice Printing

### 4.1 Invoice Access
- [ ] After checkout, click "View Invoice"
- [ ] Invoice page loads at `/bookings/{id}/invoice`
- [ ] Hotel name and GSTIN displayed
- [ ] Guest details correct

### 4.2 GST Breakdown
- [ ] Taxable amount shown
- [ ] CGST/SGST split visible (if intra-state)
- [ ] OR IGST shown (if inter-state)
- [ ] Grand total correct

### 4.3 Print Functionality
- [ ] Click "Print Invoice" button
- [ ] Print dialog opens
- [ ] Preview shows B&W formatted invoice
- [ ] Amount in words displayed

### 4.4 Thermal Mode
- [ ] Click "Thermal Mode" button
- [ ] Invoice reformats for 80mm width
- [ ] Still readable

---

## Test 5: Email System

### 5.1 Password Reset (if SMTP configured)
- [ ] Navigate to `/forgot-password`
- [ ] Enter valid email
- [ ] Email sent (check inbox or `logs/emails.log`)
- [ ] Reset link works

### 5.2 Email Logging (if SMTP not configured)
- [ ] Trigger password reset
- [ ] Check `logs/emails.log`
- [ ] Email logged with subject and recipient

---

## Test 6: Housekeeping

- [ ] Login as Housekeeping role
- [ ] Navigate to `/housekeeping`
- [ ] See list of rooms
- [ ] Mark room as "Clean"
- [ ] Status updates successfully

---

## Test 7: Reports

### 7.1 Daily Report
- [ ] Navigate to `/reports`
- [ ] Select "Daily Report" tab
- [ ] Today's summary displays

### 7.2 Police C-Form
- [ ] Navigate to `/reports/police`
- [ ] List of guests shown
- [ ] Export functionality works

---

## Test 8: Mobile Responsiveness

- [ ] Open app on mobile browser (Chrome DevTools)
- [ ] Login page responsive
- [ ] Dashboard layout adapts
- [ ] Booking creation works on mobile
- [ ] Invoice viewable on mobile

---

## Test Results Summary

| Test Area | Pass | Fail | Notes |
|-----------|------|------|-------|
| Authentication | | | |
| Shift Management | | | |
| Booking Flow | | | |
| Invoice Printing | | | |
| Email System | | | |
| Housekeeping | | | |
| Reports | | | |
| Mobile | | | |

---

## Automated UAT

Run existing test script:
```bash
cd c:\Users\HP\Documents\HotelOS
php tests/uat_simulation.php
```

**Expected Output**: `🎉 UAT SIMULATION PASSED!`

---

## Issues Found

| # | Description | Severity | Status |
|---|-------------|----------|--------|
| 1 | | | |
| 2 | | | |
| 3 | | | |

---

*Checklist completed by: _______________*  
*Date: _______________*
