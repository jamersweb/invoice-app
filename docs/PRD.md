# Product Requirements Document (PRD)
## Invoice-Discounting Platform

**Version**: 1.0  
**Date**: October 2025  
**Status**: MVP/Production Ready

---

## 1. Executive Summary

The Invoice-Discounting Platform enables suppliers to get immediate funding for their outstanding invoices by selling receivables to a financing partner. The platform manages the complete lifecycle from supplier onboarding (KYB/KYC) through invoice submission, offer generation, acceptance, funding, and repayment reconciliation.

### Key Features
- Supplier self-service onboarding with KYC/KYB verification
- Invoice submission with OCR prefill
- Automated offer generation based on configurable pricing rules
- Electronic signature for legal agreements
- Secure banking details management with masking
- Admin workflow queues (KYB review, collections, funding)
- Comprehensive audit logging and RBAC

---

## 2. Product Goals

### Business Objectives
- **Revenue**: Enable financing of invoices at scale
- **Risk Management**: Automated KYC/KYB verification and risk grading
- **Operational Efficiency**: Reduce manual processing through automation
- **Compliance**: Meet PDPL/GDPR requirements with full audit trails

### Success Metrics
- Onboarding completion rate: >70%
- Average time-to-funding: <48 hours (after KYB approval)
- OCR confidence threshold: >80%
- Duplicate detection accuracy: >95%
- System uptime: >99.5%

---

## 3. User Personas

### Supplier
- **Profile**: SME business owner needing working capital
- **Goals**: Quick onboarding, fast funding, transparent pricing
- **Pain Points**: Lengthy approval processes, unclear requirements

### Admin/Analyst
- **Profile**: Operations team reviewing applications and managing workflows
- **Goals**: Efficient review, clear queues, auditability
- **Pain Points**: Manual processes, missing documentation

### Compliance Officer
- **Profile**: Ensuring regulatory compliance and risk management
- **Goals**: Complete audit trails, data protection, retention policies
- **Pain Points**: Incomplete records, data breaches

---

## 4. Functional Requirements

### 4.1 Public Site & Lead Capture
- Marketing pages (Home, How It Works, FAQs, Contact)
- CMS-driven content management
- "Apply Now" funnel with email verification
- Lead export for sales team

### 4.2 Supplier Onboarding
- Multi-step KYC/KYB form with auto-save
- Document upload (trade license, MoA, IDs, bank letters)
- Configurable checklist by customer type
- Status tracking dashboard
- Email notifications for status changes

### 4.3 Legal Agreements
- Template management with versioning
- Electronic signature with IP/UA/timestamp capture
- Signed PDF storage
- Agreement blocking for invoice submission

### 4.4 Banking Details
- IBAN/SWIFT capture with encryption
- Masking for non-admin roles
- Funding logs (append-only)
- CSV export

### 4.5 Invoice Submission
- Form with attachments (PDF/PO/GRN)
- OCR prefill (optional, with confidence scoring)
- Duplicate detection (supplier + invoice# + amount tolerance)
- Status workflow (draft → under_review → offered → accepted → funded)

### 4.6 Offer & Acceptance
- Pricing engine with configurable rules (bands, tenor brackets)
- VIP rate adjustments (-0.5%)
- Expiry windows (48h standard / 72h VIP)
- Decline limits (block after 3 declines)
- Immutable offer terms snapshot

### 4.7 Admin Console
- KYB review queue (filters, assignment, approve/reject)
- Funding queue and batch execution
- Collections queue (overdue invoices)
- Reporting dashboards (KPIs, aging, top suppliers)
- CSV exports

### 4.8 Security & Compliance
- RBAC with roles (Admin, Analyst, Collector, Supplier)
- Field-level masking for PII
- Comprehensive audit logging
- Encryption at rest for sensitive data

---

## 5. Non-Functional Requirements

See `NFR.md` for detailed specifications.

---

## 6. Technical Architecture

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue 3 + Inertia.js + Tailwind CSS
- **Database**: MySQL/PostgreSQL with encryption
- **File Storage**: Local/S3 with public/private disks
- **Queue**: Database/Redis queue for async jobs
- **PDF Generation**: DomPDF
- **OCR**: Tesseract/AWS Textract adapter interface

---

## 7. Data Model

See migrations for schema. Key entities:
- Users, Suppliers, Buyers
- Documents (with types, status, expiry)
- Agreements (with templates, signatures)
- Invoices, Offers, Fundings
- Expected/Received Repayments
- Audit Events

---

## 8. Integration Points

- **Email**: Laravel Mail (SMTP/SES)
- **SMS**: Placeholder for SMS provider
- **OCR**: Adapter interface (stub: Tesseract)
- **E-ID/KYC**: Adapter interface (stub: MockKycProvider)

---

## 9. Security Requirements

- Authentication: Laravel Sanctum
- Authorization: Spatie Permission (RBAC)
- Encryption: Laravel encryption for sensitive fields
- CSRF: Laravel default
- Rate Limiting: Throttle middleware
- Audit: Complete event logging

---

## 10. Compliance & Privacy

- **PDPL/GDPR**: Consent tracking, data retention, right to erasure
- **Audit**: Immutable audit logs for all sensitive operations
- **Data Masking**: PII masked in non-admin views
- **Retention**: Soft deletes, configurable retention schedules

---

## 11. Future Enhancements (Phase 2)

- Advanced scorecards with behavioral data
- Buyer confirmations (PO/GRN verification)
- Deeper OCR training
- Webhooks for ERP integrations
- Multi-currency support
- SLA dashboards and turn-time KPIs

---

## 12. Acceptance Criteria

### Core Workflows
- ✅ Supplier can complete onboarding and receive KYB approval
- ✅ Supplier can submit invoices and receive offers
- ✅ Admin can review documents and approve/reject
- ✅ Offers are generated with correct pricing
- ✅ Funding logs are recorded and exportable
- ✅ All actions are audited

### Performance
- Page load: <2s
- API response: <500ms
- File upload: Supports up to 10MB
- Concurrent users: 100+ (scalable)

### Security
- All sensitive fields encrypted
- RBAC enforced on all endpoints
- Audit trail for all state changes
- No PII in logs

---

## 13. Glossary

- **KYB**: Know Your Business
- **KYC**: Know Your Customer
- **IBAN**: International Bank Account Number
- **SWIFT**: Society for Worldwide Interbank Financial Telecommunication
- **OCR**: Optical Character Recognition
- **PDPL**: Personal Data Protection Law (Saudi Arabia)
- **RBAC**: Role-Based Access Control
- **TRN**: Tax Registration Number
- **MoA**: Memorandum of Association

---

**Document Owner**: Product Team  
**Last Updated**: October 2025  
**Next Review**: Post-launch (T+30 days)








