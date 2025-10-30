# Quick Testing Guide

Use this guide to manually test all features in your invoice app.

## Prerequisites

1. Ensure your database is seeded:
```bash
php artisan db:seed
```

2. Create test users (or use seeders):
- Admin user (role: Admin)
- Supplier user (role: Supplier)
- Analyst user (role: Analyst)

---

## Supplier Features Testing

### 1. Register
1. Navigate to `/register`
2. Fill in name, email, password
3. Submit form
4. ✅ Verify: User created and logged in

### 2. Upload Trade License
1. Login as supplier
2. Navigate to `/documents/upload`
3. Select document type (Trade License)
4. Upload PDF/image file
5. Enter expiry date if required
6. Submit
7. ✅ Verify: Document appears in `/documents` with status "pending_review"

### 3. E-sign Agreement
1. Navigate to `/agreements`
2. Select an agreement template
3. Click "Sign"
4. ✅ Verify: Agreement status changes to "signed" in list

### 4. Add Bank Details
1. Navigate to `/bank`
2. Fill in bank details (bank name, account number, IBAN, etc.)
3. Submit
4. ✅ Verify: Bank account created and displayed

### 5. Submit Invoice
1. Ensure agreement is signed first
2. Navigate to invoice submission page
3. Fill in invoice details:
   - Supplier ID
   - Buyer ID
   - Invoice Number
   - Amount
   - Currency
   - Due Date
4. Upload invoice file (PDF/image)
5. Submit
6. ✅ Verify: Invoice created with status "draft"
7. ✅ Verify: Invoice moves to "under_review" after OCR processing

### 6. View Status
**Recent Invoices:**
- API: `GET /api/v1/me/invoices/recent`
- ✅ Verify: Returns list of supplier's invoices

**Active Offers:**
- API: `GET /api/v1/me/offers/active`
- ✅ Verify: Returns active offers for supplier

**Repayment Schedule:**
- API: `GET /api/v1/me/repayments/schedule`
- ✅ Verify: Returns expected repayments

---

## Admin Features Testing

### 1. Review/Approve KYB
1. Login as Admin
2. Navigate to `/admin/kyb-queue`
3. View pending documents
4. Click "Claim" to assign to yourself
5. Review document
6. Click "Approve" or "Reject"
7. Enter notes and grade (for approve)
8. Submit
9. ✅ Verify: Document status updated
10. ✅ Verify: Supplier status updated
11. ✅ Verify: Email notification sent (check logs)

### 2. Review Invoices
**Current Status**: This endpoint returns 501 (Not Implemented)
- Route: `POST /api/v1/admin/invoices/{id}/approve`
- **Workaround**: OCR automatically reviews invoices

### 3. Apply Pricing Rule
1. Login as Admin
2. Navigate to `/admin/pricing-rules`
3. Click "Create New Rule"
4. Fill in:
   - Tenor Min/Max (days)
   - Amount Min/Max
   - Base Rate
   - VIP Adjustment (optional)
5. Save
6. ✅ Verify: Rule appears in list
7. ✅ Verify: Rule used when issuing offers

### 4. Issue Offer
1. Login as Admin
2. Find an invoice with status "under_review"
3. POST to `/offers/issue` with:
   ```json
   {
     "invoice_id": 1,
     "supplier_grade": "B",
     "buyer_grade": "B",
     "historical_default_rate": 0.0
   }
   ```
4. ✅ Verify: Offer created with status "issued"
5. ✅ Verify: Invoice status updates
6. ✅ Verify: Pricing calculated correctly

### 5. Record Funding
**Option A: Batch Funding**
1. Create funding batch: `POST /api/v1/admin/funding-batches`
2. Approve batch: `POST /api/v1/admin/funding-batches/{id}/approve`
3. Execute batch: `POST /api/v1/admin/funding-batches/{id}/execute`
4. ✅ Verify: Fundings status = "executed"
5. ✅ Verify: Expected repayments created

**Option B: Individual Funding**
1. `POST /api/v1/admin/fundings/{id}/record`
2. ✅ Verify: Funding status = "executed"

### 6. Track Expected Repayment
1. View repayments: `GET /api/v1/admin/repayments/unallocated`
2. Check expected: `GET /api/v1/me/repayments/schedule`
3. ✅ Verify: Expected repayments shown with due dates
4. ✅ Verify: Status tracking (open/partial/settled/overdue)

### 7. Mark Repayment Received
1. `POST /api/v1/admin/repayments` with:
   ```json
   {
     "buyer_id": 1,
     "amount": 1000.00,
     "received_date": "2024-01-15",
     "bank_reference": "REF-001"
   }
   ```
2. ✅ Verify: Received repayment created
3. ✅ Verify: Auto-allocated to expected repayments (FIFO)
4. ✅ Verify: Invoice status updates to "settled" when fully paid

---

## System Features Testing

### 1. Audit Logging
1. Perform any action (upload doc, approve KYB, etc.)
2. Navigate to `/admin/audit-log` (as Admin)
3. ✅ Verify: Action logged with:
   - Timestamp
   - User ID
   - Entity type/ID
   - Action description
   - Diff JSON (old/new values)
   - IP address
   - User agent

### 2. Exports
**KYB Queue Export:**
1. Navigate to `/admin/kyb-queue`
2. Apply filters if needed
3. Click "Export" or access `/admin/kyb-queue/export`
4. ✅ Verify: CSV downloaded
5. ✅ Verify: Data matches database

**Audit Log Export:**
1. Navigate to `/admin/audit-log`
2. Access `/admin/api/audit-log/export`
3. ✅ Verify: Export downloaded
4. ✅ Verify: Data matches audit_events table

### 3. Email Notifications
1. Approve a KYB document
2. ✅ Verify: Email sent to supplier (check logs)
3. Check mail logs: `storage/logs/laravel.log`
4. ✅ Verify: Notification includes status update

**Test Notification Triggers:**
- ✅ KYB approval → Email sent
- ✅ KYB rejection → Email sent
- ✅ Supplier approved → Welcome email sent
- ⚠️ Invoice status changes → Needs verification

---

## API Testing Examples

### Using cURL

**Register Supplier:**
```bash
curl -X POST http://localhost/register \
  -d "name=Test Supplier" \
  -d "email=supplier@test.com" \
  -d "password=Password123!" \
  -d "password_confirmation=Password123!"
```

**Upload Document:**
```bash
curl -X POST http://localhost/documents \
  -H "Cookie: laravel_session=..." \
  -F "document_type_id=1" \
  -F "file=@license.pdf" \
  -F "expiry_at=2025-12-31"
```

**Issue Offer:**
```bash
curl -X POST http://localhost/offers/issue \
  -H "Cookie: laravel_session=..." \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_id": 1,
    "supplier_grade": "B",
    "buyer_grade": "B"
  }'
```

---

## Checklist

Use this checklist to verify all features:

- [ ] Supplier registration works
- [ ] Document upload works
- [ ] Agreement signing works
- [ ] Bank details can be added
- [ ] Invoice submission works
- [ ] Status viewing works (all APIs)
- [ ] KYB review/approval works
- [ ] Pricing rules can be created/edited
- [ ] Offers can be issued
- [ ] Funding can be recorded
- [ ] Expected repayments are tracked
- [ ] Repayments can be marked received
- [ ] Audit logging works
- [ ] Exports download and match database
- [ ] Email notifications are sent

---

## Troubleshooting

**Issue**: Invoice submission fails with "agreement required"
- **Fix**: Sign an agreement first at `/agreements`

**Issue**: Can't approve KYB (403 error)
- **Fix**: Ensure user has "review_documents" permission

**Issue**: Offer issuance fails
- **Fix**: Create pricing rules first at `/admin/pricing-rules`

**Issue**: Emails not sending
- **Fix**: Check `.env` mail configuration
- **Fix**: Check `storage/logs/laravel.log` for errors

**Issue**: Export returns empty
- **Fix**: Ensure data exists in database
- **Fix**: Check filters aren't too restrictive



