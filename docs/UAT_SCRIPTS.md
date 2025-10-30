# UAT Test Scripts
## Invoice-Discounting Platform End-to-End Testing

**Version**: 1.0  
**Date**: October 2025  
**Test Environment**: Staging  
**Testers**: [Names]

---

## Test Scenario 1: Complete Supplier Onboarding Journey

### Preconditions
- Clean test database
- Admin user exists (`admin@example.com`)
- Document types seeded

### Steps

1. **Public Site Access**
   - [ ] Navigate to `/`
   - [ ] Verify homepage loads with CMS content
   - [ ] Click "Apply Now"
   - [ ] Verify redirect to `/apply`

2. **Lead Capture**
   - [ ] Enter email: `supplier@test.com`
   - [ ] Enter phone: `+966501234567`
   - [ ] Submit form
   - [ ] Verify redirect to `/apply/step2`
   - [ ] Verify email sent (check logs)

3. **Email Verification**
   - [ ] Open email
   - [ ] Click verification link
   - [ ] Verify status changes to "verified"
   - [ ] Verify redirect to step 2

4. **Company Details**
   - [ ] Enter company name: "Test Trading Co"
   - [ ] Enter contact name: "John Doe"
   - [ ] Submit
   - [ ] Verify supplier record created

5. **Registration**
   - [ ] Register account with email `supplier@test.com`
   - [ ] Verify email verification required
   - [ ] Verify email sent
   - [ ] Click verification link
   - [ ] Login

6. **KYC/KYB Form**
   - [ ] Navigate to `/onboarding/kyc`
   - [ ] Fill Step 1: Company Profile
     - Legal name: "Test Trading Co LLC"
     - TRN: "123456789"
     - Business type: "LLC"
     - Industry: "Trading"
     - Country: "SA"
     - City: "Riyadh"
   - [ ] Save and continue
   - [ ] Verify data persists (refresh page)

7. **Document Upload**
   - [ ] Upload trade license (PDF)
   - [ ] Select document type: "Commercial Registration"
   - [ ] Add expiry date
   - [ ] Upload
   - [ ] Verify document appears in list
   - [ ] Upload MoA
   - [ ] Upload bank letter

8. **Submit KYC**
   - [ ] Review all sections
   - [ ] Click "Submit for Review"
   - [ ] Verify status changes to "under_review"
   - [ ] Verify redirect to `/kyc-status`

9. **Admin Review**
   - [ ] Login as admin
   - [ ] Navigate to `/admin/kyb-queue`
   - [ ] Find supplier's documents
   - [ ] Click to review
   - [ ] View document preview
   - [ ] Add review notes
   - [ ] Click "Approve"
   - [ ] Verify status changes to "approved"
   - [ ] Verify welcome email sent

10. **Verification**
    - [ ] Login as supplier
    - [ ] Navigate to `/kyc-status`
    - [ ] Verify status shows "approved"
    - [ ] Verify completion percentage: 100%

### Expected Results
- ✅ Supplier can complete onboarding without errors
- ✅ All documents uploaded and accessible
- ✅ Admin can review and approve
- ✅ Status updates propagate correctly
- ✅ Emails sent successfully

### Test Data
- Supplier Email: `supplier@test.com`
- Admin Email: `admin@example.com`
- Documents: Sample PDF files in test data directory

---

## Test Scenario 2: Agreement Signing & Invoice Submission

### Preconditions
- Supplier KYC approved (from Scenario 1)
- Agreement template exists
- Supplier logged in

### Steps

1. **View Agreements**
   - [ ] Navigate to `/agreements`
   - [ ] Verify template list displayed
   - [ ] Click on template
   - [ ] Verify template preview

2. **Sign Agreement**
   - [ ] Click "Sign Agreement"
   - [ ] Verify PDF generated
   - [ ] Verify signature flow
   - [ ] Complete signing
   - [ ] Verify signed PDF stored
   - [ ] Verify audit event created (IP, UA, timestamp)

3. **Upload Banking Details**
   - [ ] Navigate to `/bank`
   - [ ] Enter account name: "Test Trading Co"
   - [ ] Enter IBAN: "SA0380000000608010167519"
   - [ ] Enter SWIFT: "ALBKSAJE"
   - [ ] Enter bank name: "Al Rajhi Bank"
   - [ ] Upload bank letter
   - [ ] Save
   - [ ] Verify masked view (non-admin)
   - [ ] Verify admin sees full details

4. **Submit Invoice**
   - [ ] Navigate to invoice submission (or create route)
   - [ ] Verify agreement check passes
   - [ ] Fill invoice form:
     - Buyer: Select from list
     - Invoice number: "INV-2025-001"
     - Invoice date: Today
     - Due date: +30 days
     - Amount: 10000.00
     - Currency: SAR
   - [ ] Upload invoice PDF
   - [ ] Submit
   - [ ] Verify invoice created with status "draft"
   - [ ] Verify OCR job dispatched
   - [ ] Verify duplicate check runs

5. **OCR Processing**
   - [ ] Wait for OCR job to complete
   - [ ] Verify `ocr_data` populated
   - [ ] Verify `ocr_confidence` set
   - [ ] If low confidence, verify manual review queue

6. **Verification**
   - [ ] Verify invoice status transitions
   - [ ] Verify audit events logged
   - [ ] Verify file stored correctly

### Expected Results
- ✅ Agreement signing captures full audit trail
- ✅ Invoice submission blocked without signed agreements
- ✅ OCR extracts data correctly (or flags for review)
- ✅ Duplicate detection works

---

## Test Scenario 3: Offer Generation & Acceptance

### Preconditions
- Invoice submitted (from Scenario 2)
- Supplier grade: B
- Pricing rules configured

### Steps

1. **Admin Issues Offer**
   - [ ] Login as admin
   - [ ] Navigate to invoice details
   - [ ] Click "Issue Offer"
   - [ ] Verify pricing calculated:
     - Discount rate based on rules
     - Admin fee calculated
     - Net amount calculated
   - [ ] Verify offer created with status "issued"
   - [ ] Verify expiry date set (48h standard / 72h VIP)
   - [ ] Verify offer visible to supplier

2. **Supplier Views Offer**
   - [ ] Login as supplier
   - [ ] Navigate to `/customer/dashboard`
   - [ ] Verify "Active Offers" widget shows offer
   - [ ] Click to view details
   - [ ] Verify terms displayed
   - [ ] Verify pricing snapshot visible

3. **Accept Offer**
   - [ ] Click "Accept Offer"
   - [ ] Confirm acceptance
   - [ ] Verify invoice status changes to "pending_funding"
   - [ ] Verify offer status changes to "accepted"
   - [ ] Verify immutable terms recorded
   - [ ] Verify audit event created

4. **Decline Offer (Alternative)**
   - [ ] Create new offer
   - [ ] Click "Decline"
   - [ ] Enter decline reason
   - [ ] Verify offer status: "declined"
   - [ ] Verify invoice status: "approved" (can re-offer)
   - [ ] Verify decline count tracked

5. **VIP Discount Test**
   - [ ] Mark supplier as VIP (in kyc_data)
   - [ ] Issue new offer
   - [ ] Verify rate adjusted -0.5%
   - [ ] Verify expiry: 72h

6. **Decline Limit Test**
   - [ ] Decline 3 offers
   - [ ] Try to issue 4th offer
   - [ ] Verify blocked with error message

### Expected Results
- ✅ Pricing calculations correct
- ✅ VIP discounts apply
- ✅ Decline limits enforced
- ✅ Acceptance creates funding record
- ✅ Terms immutable after acceptance

---

## Test Scenario 4: Funding & Repayment Flow

### Preconditions
- Offer accepted (from Scenario 3)
- Funding batch created

### Steps

1. **Create Funding Batch**
   - [ ] Login as admin
   - [ ] Navigate to funding queue
   - [ ] Select invoices for batch
   - [ ] Create batch
   - [ ] Verify batch status: "pending_approval"

2. **Approve Batch**
   - [ ] Review batch
   - [ ] Click "Approve"
   - [ ] Verify status: "approved"

3. **Execute Batch**
   - [ ] Click "Execute"
   - [ ] Verify funding records created
   - [ ] Verify invoice status: "funded"
   - [ ] Verify `ExpectedRepayment` records created
   - [ ] Verify batch status: "completed"

4. **Record Funding Log**
   - [ ] Navigate to `/admin/funding-logs`
   - [ ] Click "New Record"
   - [ ] Fill form:
     - Supplier: Select supplier
     - Transfer date: Today
     - Amount: 9500.00
     - Currency: SAR
     - Bank reference: "TXN-001"
   - [ ] Save
   - [ ] Verify log created
   - [ ] Verify audit event

5. **Record Repayment**
   - [ ] Navigate to repayments API
   - [ ] POST received repayment:
     - Amount: 5000.00
     - Received date: Today
     - Reference: "PAY-001"
   - [ ] Verify allocation job runs
   - [ ] Verify FIFO allocation (oldest first)
   - [ ] Verify partial payments handled

6. **Manual Allocation**
   - [ ] Navigate to unallocated repayments
   - [ ] Select repayment
   - [ ] Manually allocate to expected repayment
   - [ ] Verify allocation created
   - [ ] Verify status updates

7. **Overdue Handling**
   - [ ] Create expected repayment with past due date
   - [ ] Run overdue job (or trigger manually)
   - [ ] Verify status: "overdue"
   - [ ] Verify collections queue populated

### Expected Results
- ✅ Batch lifecycle works end-to-end
- ✅ Expected repayments created on funding
- ✅ FIFO allocation correct
- ✅ Overdue detection works
- ✅ Collections queue populated

---

## Test Scenario 5: Collections & Reminders

### Preconditions
- Overdue invoices exist

### Steps

1. **View Collections Queue**
   - [ ] Login as admin/collector
   - [ ] Navigate to `/admin/collections`
   - [ ] Verify overdue invoices listed
   - [ ] Apply filters (age, amount, status)
   - [ ] Verify filtering works

2. **Claim Invoice**
   - [ ] Click "Claim"
   - [ ] Verify assigned_to updated
   - [ ] Verify audit event created
   - [ ] Verify invoice removed from "unassigned" view

3. **Send Reminder**
   - [ ] Select invoice
   - [ ] Click "Send Reminder"
   - [ ] Verify email sent
   - [ ] Verify email template correct (EN/AR)
   - [ ] Verify audit event logged

4. **Reassign Invoice**
   - [ ] Select assigned invoice
   - [ ] Click "Reassign"
   - [ ] Select new collector
   - [ ] Save
   - [ ] Verify assignment updated
   - [ ] Verify audit trail

### Expected Results
- ✅ Collections queue displays correctly
- ✅ Assignment works
- ✅ Reminders sent with correct templates
- ✅ Audit trail complete

---

## Test Scenario 6: Admin Reporting & Exports

### Preconditions
- Multiple suppliers, invoices, offers exist
- Admin logged in

### Steps

1. **Dashboard Metrics**
   - [ ] Navigate to `/dashboard`
   - [ ] Verify KPIs display:
     - Total funded
     - Total outstanding
     - Overdue amount
   - [ ] Verify revenue chart renders
   - [ ] Apply date filters
   - [ ] Verify metrics update

2. **Aging Analysis**
   - [ ] Verify "Repayment Aging" widget
   - [ ] Verify buckets: current, 1-30, 31-60, 60+
   - [ ] Verify amounts correct

3. **Top Suppliers**
   - [ ] Verify "Top Suppliers" widget
   - [ ] Verify sorting by total funded
   - [ ] Verify limit (top 10)

4. **Export Funding Logs**
   - [ ] Navigate to `/admin/funding-logs`
   - [ ] Apply filters
   - [ ] Click "Export CSV"
   - [ ] Verify CSV downloaded
   - [ ] Verify columns correct
   - [ ] Verify data matches filters

5. **Export Leads**
   - [ ] Navigate to `/admin/leads`
   - [ ] Click "Export"
   - [ ] Verify CSV downloaded
   - [ ] Verify all leads included

6. **Export KYB Queue**
   - [ ] Navigate to `/admin/kyb-queue`
   - [ ] Click "Export CSV"
   - [ ] Verify export includes filters

### Expected Results
- ✅ All metrics accurate
- ✅ Charts render correctly
- ✅ Exports include correct data
- ✅ Filters applied to exports

---

## Test Scenario 7: Security & RBAC

### Preconditions
- Multiple users with different roles
- Test data exists

### Steps

1. **Role Permissions**
   - [ ] Login as Supplier
   - [ ] Try to access `/admin/kyb-queue`
   - [ ] Verify 403 Forbidden
   - [ ] Try to access `/admin/funding-logs`
   - [ ] Verify 403 Forbidden
   - [ ] Verify own data accessible

2. **Field Masking**
   - [ ] Login as Supplier
   - [ ] View own bank account
   - [ ] Verify IBAN masked (last 4 only)
   - [ ] Login as Admin
   - [ ] View same bank account
   - [ ] Verify full IBAN visible

3. **Audit Logging**
   - [ ] Perform sensitive action (approve document)
   - [ ] Verify audit event created
   - [ ] Verify IP address logged
   - [ ] Verify User-Agent logged
   - [ ] Verify diff_json includes old/new values

4. **Rate Limiting**
   - [ ] Submit multiple requests rapidly
   - [ ] Verify rate limit enforced
   - [ ] Verify 429 Too Many Requests response

5. **CSRF Protection**
   - [ ] Make POST request without CSRF token
   - [ ] Verify 419 error

### Expected Results
- ✅ RBAC enforced on all endpoints
- ✅ Masking works correctly
- ✅ Audit logging comprehensive
- ✅ Rate limiting protects endpoints
- ✅ CSRF protection active

---

## Test Scenario 8: Error Handling & Edge Cases

### Steps

1. **Invalid Data**
   - [ ] Submit form with invalid IBAN
   - [ ] Verify validation error
   - [ ] Submit invoice with duplicate number
   - [ ] Verify duplicate warning
   - [ ] Submit offer after 3 declines
   - [ ] Verify blocking error

2. **File Upload Limits**
   - [ ] Upload file > 10MB
   - [ ] Verify rejection
   - [ ] Upload invalid file type
   - [ ] Verify rejection

3. **Concurrent Actions**
   - [ ] Two admins try to approve same document
   - [ ] Verify only first succeeds
   - [ ] Verify optimistic locking or status check

4. **Missing Data**
   - [ ] Try to create invoice without supplier
   - [ ] Verify error
   - [ ] Try to issue offer without pricing rules
   - [ ] Verify fallback or error

5. **Expired Offers**
   - [ ] Wait for offer to expire
   - [ ] Try to accept
   - [ ] Verify rejection
   - [ ] Verify status: "expired"

### Expected Results
- ✅ Validation prevents invalid data
- ✅ Errors are user-friendly
- ✅ System handles edge cases gracefully
- ✅ No data corruption

---

## Test Results Summary

| Scenario | Status | Tester | Date | Notes |
|----------|--------|--------|------|-------|
| 1. Onboarding | ⬜ | | | |
| 2. Agreements & Invoices | ⬜ | | | |
| 3. Offers | ⬜ | | | |
| 4. Funding & Repayments | ⬜ | | | |
| 5. Collections | ⬜ | | | |
| 6. Reporting | ⬜ | | | |
| 7. Security | ⬜ | | | |
| 8. Edge Cases | ⬜ | | | |

**Overall Status**: ⬜ Pass / ⬜ Fail / ⬜ Conditional Pass

**Sign-Off**:
- **QA Lead**: _________________ Date: ___________
- **Product Owner**: _________________ Date: ___________

---

## Known Issues

| Issue | Severity | Scenario | Status |
|-------|----------|----------|--------|
| | | | |

---

**Document Owner**: QA Team  
**Review Frequency**: Per release cycle



