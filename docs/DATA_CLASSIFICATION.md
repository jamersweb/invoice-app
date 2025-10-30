# Data Classification Matrix
## Invoice-Discounting Platform

**Version**: 1.0  
**Date**: October 2025

---

## 1. Classification Levels

### 🔴 **PII/Highly Sensitive** (Red)
**Definition**: Personally Identifiable Information that could identify an individual or business.

**Examples**:
- Bank account numbers (IBAN, account names)
- Tax Registration Numbers (TRN)
- Government-issued IDs
- Phone numbers, email addresses
- Company registration documents

**Protection Requirements**:
- ✅ Encryption at rest (Laravel encryption casts)
- ✅ Masking in logs and non-admin views
- ✅ Access restricted to Admin role only
- ✅ Audit logging for all access
- ✅ Retention: 7 years (financial records)

---

### 🟡 **Business Sensitive** (Yellow)
**Definition**: Business information that could impact operations if exposed.

**Examples**:
- Invoice amounts and numbers
- Pricing rules and discount rates
- Supplier risk grades
- Funding amounts and dates
- Expected repayment schedules

**Protection Requirements**:
- ✅ Access control via RBAC (role-based)
- ✅ Audit logging for modifications
- ✅ Masking in customer-facing views (partial)
- ✅ Retention: 7 years

---

### 🟢 **Internal/Public** (Green)
**Definition**: Information that can be shared internally or publicly.

**Examples**:
- Company names (after consent)
- Public marketing content (CMS blocks)
- General FAQs
- Feature descriptions

**Protection Requirements**:
- ✅ Basic access control
- ✅ Standard retention policies
- ✅ No encryption required

---

## 2. Data Categories by Entity

### Users
| Field | Classification | Encryption | Masking | Retention |
|-------|---------------|------------|---------|-----------|
| email | 🔴 PII | ✅ | ❌ | 7 years |
| password | 🔴 PII | ✅ (hashed) | ✅ | Until deletion |
| name | 🟡 Business | ❌ | ❌ | 7 years |

### Suppliers
| Field | Classification | Encryption | Masking | Retention |
|-------|---------------|------------|---------|-----------|
| contact_email | 🔴 PII | ❌ | ❌ | 7 years |
| tax_registration_number | 🔴 PII | ✅ | ✅ (non-admin) | 7 years |
| contact_phone | 🔴 PII | ❌ | ✅ (partial) | 7 years |
| company_name | 🟡 Business | ❌ | ❌ | 7 years |
| legal_name | 🟡 Business | ❌ | ❌ | 7 years |
| kyb_status | 🟡 Business | ❌ | ❌ | 7 years |
| grade | 🟡 Business | ❌ | ❌ | 7 years |
| kyc_data (JSON) | 🔴 PII | ✅ | ✅ | 7 years |

### Bank Accounts
| Field | Classification | Encryption | Masking | Retention |
|-------|---------------|------------|---------|-----------|
| account_name | 🔴 PII | ✅ | ✅ (non-admin) | 7 years |
| iban | 🔴 PII | ✅ | ✅ (last 4 only) | 7 years |
| swift | 🔴 PII | ✅ | ✅ (partial) | 7 years |
| bank_name | 🟡 Business | ✅ | ✅ (non-admin) | 7 years |
| branch | 🟡 Business | ✅ | ✅ (non-admin) | 7 years |

### Invoices
| Field | Classification | Encryption | Masking | Retention |
|-------|---------------|------------|---------|-----------|
| invoice_number | 🟡 Business | ❌ | ❌ | 7 years |
| amount | 🟡 Business | ❌ | ❌ | 7 years |
| buyer_id | 🟡 Business | ❌ | ❌ | 7 years |
| supplier_id | 🟡 Business | ❌ | ❌ | 7 years |
| file_path | 🟡 Business | ❌ | ❌ | 7 years |
| ocr_data | 🟡 Business | ❌ | ❌ | 7 years |

### Offers
| Field | Classification | Encryption | Masking | Retention |
|-------|---------------|------------|---------|-----------|
| discount_rate | 🟡 Business | ❌ | ❌ | 7 years |
| net_amount | 🟡 Business | ❌ | ❌ | 7 years |
| pricing_snapshot | 🟡 Business | ❌ | ❌ | Immutable |

### Funding Logs
| Field | Classification | Encryption | Masking | Retention |
|-------|---------------|------------|---------|-----------|
| amount | 🟡 Business | ❌ | ❌ | 7 years |
| bank_reference | 🟡 Business | ❌ | ❌ | 7 years |
| internal_reference | 🟡 Business | ❌ | ❌ | 7 years |
| supplier_id | 🟡 Business | ❌ | ❌ | 7 years |

### Documents
| Field | Classification | Encryption | Masking | Retention |
|-------|---------------|------------|---------|-----------|
| file_path | 🔴 PII | ❌ | ❌ | 7 years |
| document_type_id | 🟡 Business | ❌ | ❌ | 7 years |
| expiry_at | 🟡 Business | ❌ | ❌ | 7 years |
| review_notes | 🟡 Business | ❌ | ❌ | 7 years |

### Audit Events
| Field | Classification | Encryption | Masking | Retention |
|-------|---------------|------------|---------|-----------|
| actor_id | 🟡 Business | ❌ | ❌ | 10 years |
| entity_id | 🟡 Business | ❌ | ❌ | 10 years |
| diff_json | 🔴 PII | ❌ | ✅ (PII fields) | 10 years |
| ip | 🟡 Business | ❌ | ✅ (last octet) | 10 years |
| ua | 🟡 Business | ❌ | ❌ | 10 years |

---

## 3. Masking Rules

### IBAN
- **Pattern**: `****1234` (show last 4 digits)
- **Implementation**: `BankAccount::getMaskedIbanAttribute()`

### Account Name
- **Pattern**: `First ****` (show first word, mask rest)
- **Implementation**: `BankAccount::getMaskedAccountNameAttribute()`

### SWIFT Code
- **Pattern**: `AB****CD` (show first 2, last 2, mask middle)
- **Implementation**: `BankAccount::getMaskedSwiftAttribute()`

### IP Address
- **Pattern**: `192.168.1.***` (mask last octet)
- **Implementation**: In audit log exports

### Tax Registration Number
- **Pattern**: `***456` (mask leading digits)
- **Implementation**: Supplier profile views (non-admin)

---

## 4. Retention Policies

### Active Records
- **Duration**: Indefinitely (until soft deleted)
- **Entities**: Invoices, Offers, Suppliers, Agreements

### Soft Deleted Records
- **Duration**: 7 years
- **Entities**: All business entities
- **Automation**: Scheduled job to permanently delete after retention period

### Audit Logs
- **Duration**: 10 years (compliance)
- **Entities**: `audit_events` table
- **Archival**: Partitioned tables for performance

### File Storage
- **Duration**: 7 years (aligned with record retention)
- **Entities**: Documents, invoices, signed PDFs
- **Cleanup**: Scheduled job after retention period

### Backup Retention
- **Daily Backups**: 30 days
- **Monthly Backups**: 12 months
- **Yearly Backups**: 7 years

---

## 5. Access Control Matrix

| Role | PII Access | Business Data | Audit Logs | Admin Functions |
|------|-----------|---------------|-------------|----------------|
| **Admin** | ✅ Full | ✅ Full | ✅ Full | ✅ All |
| **Analyst** | ⚠️ Masked | ✅ Full | ⚠️ View Only | ⚠️ KYB Review |
| **Collector** | ⚠️ Masked | ⚠️ Limited | ⚠️ View Only | ⚠️ Collections |
| **Supplier** | ✅ Own Only | ✅ Own Only | ❌ No | ❌ No |
| **Buyer** | ❌ No | ✅ Own Only | ❌ No | ❌ No |

---

## 6. Encryption Standards

### At Rest
- **Method**: Laravel encryption (AES-256-CBC)
- **Fields**: Bank accounts, TRN, KYC data JSON
- **Implementation**: Eloquent `encrypted` cast

### In Transit
- **Method**: HTTPS/TLS 1.2+
- **Scope**: All web and API traffic
- **Enforcement**: Force HTTPS in production

### Backup Encryption
- **Method**: S3 server-side encryption (SSE)
- **Scope**: Database dumps, file backups
- **Key Management**: AWS KMS (recommended for production)

---

## 7. Data Subject Rights (PDPL/GDPR)

### Right to Access
- **Implementation**: Export endpoint `/api/v1/supplier/export`
- **Format**: Excel/CSV
- **Scope**: All personal data for logged-in user

### Right to Rectification
- **Implementation**: Profile edit endpoints
- **Audit**: All corrections logged in audit events

### Right to Erasure
- **Implementation**: Soft delete with retention period
- **Scope**: User can request deletion (admin action)
- **Retention Override**: Financial records retained per regulations

### Right to Portability
- **Implementation**: Export endpoint (same as access)
- **Format**: Machine-readable (JSON/CSV)

### Right to Object
- **Implementation**: Opt-out mechanisms (Phase 2)

---

## 8. Logging Guidelines

### ✅ What to Log
- User authentication (login/logout)
- State changes (status transitions)
- Data modifications (CRUD operations)
- File uploads/downloads
- Permission changes
- Failed access attempts

### ❌ What NOT to Log
- Plain-text passwords (never)
- Full IBANs/account numbers (masked)
- Credit card numbers (not applicable)
- Full KYC data (summarized only)

### Log Format
```json
{
  "actor_type": "App\\Models\\User",
  "actor_id": 123,
  "entity_type": "App\\Models\\Invoice",
  "entity_id": 456,
  "action": "updated",
  "diff_json": {
    "old_values": {"status": "draft"},
    "new_values": {"status": "under_review"}
  },
  "ip": "192.168.1.***",
  "ua": "Mozilla/5.0...",
  "created_at": "2025-10-29T10:00:00Z"
}
```

---

## 9. Data Minimization

### Collection Principles
- ✅ Collect only data necessary for service delivery
- ✅ Optional fields clearly marked
- ✅ Consent obtained before collection
- ✅ Data retention limited to business need

### Processing Principles
- ✅ Process only for stated purposes
- ✅ No secondary use without consent
- ✅ Aggregate/anonymize for analytics
- ✅ Delete when no longer needed

---

## 10. Incident Response

### Data Breach Procedure
1. **Detection**: Monitor audit logs and access patterns
2. **Containment**: Immediately revoke access if breach detected
3. **Assessment**: Determine scope and affected records
4. **Notification**: Notify affected users within 72 hours (GDPR) / ASAP (PDPL)
5. **Remediation**: Patch vulnerability, enhance security
6. **Documentation**: Log incident in audit trail

### Breach Notification Template
- Date/time of breach
- Nature of breach (unauthorized access, data loss)
- Affected records (count and categories)
- Mitigation steps taken
- Recommendations for users

---

**Document Owner**: Compliance Team  
**Last Updated**: October 2025  
**Review Frequency**: Annually or after major changes



