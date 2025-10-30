# Setup Guide - New Admin Console Features

This guide will help you set up and use all the newly implemented features.

## 🚀 Installation Steps

### 1. Run Migrations

```bash
cd web
php artisan migrate
```

This will create:
- `buyers` table
- `risk_grades` table
- Add review fields to `invoices` table

### 2. Seed Risk Grades

```bash
php artisan db:seed --class=RiskGradeSeeder
```

This creates default risk grades: A+, A, B+, B, C, D

### 3. Configure Environment

Add these to your `.env` file:

```env
# Data Residency (UAE/GCC)
DATA_RESIDENCY_REGION=UAE
AWS_REGION=me-south-1
DB_REGION=me-south-1

# Encryption
ENCRYPT_SENSITIVE_DATA=true

# Data Retention (years)
AUDIT_RETENTION_YEARS=7
ANALYTICS_RETENTION_YEARS=1
SOFT_DELETE_RETENTION_YEARS=2
```

### 4. Verify Setup

```bash
# Check routes
php artisan route:list | grep -E "(invoice-review|buyers|risk-grades|users|exports|statements|health)"

# Test health endpoint
curl http://localhost/health
```

## 📋 Feature Usage Guide

### Invoice Review Queue

**Access**: `/admin/invoice-review`

**Features**:
- View invoices pending review
- Claim invoices for review
- Approve/reject with notes
- Add dispute notes
- Write off invoices

**API Endpoints**:
- `GET /admin/api/invoice-review/queue` - Get queue with filters
- `POST /admin/api/invoice-review/{id}/claim` - Claim invoice
- `POST /admin/api/invoice-review/{id}/approve` - Approve invoice
- `POST /admin/api/invoice-review/{id}/reject` - Reject invoice
- `POST /admin/api/invoice-review/{id}/dispute-note` - Add dispute note
- `POST /admin/api/invoice-review/{id}/write-off` - Write off invoice

### Buyers Management

**Access**: `/admin/buyers`

**Features**:
- Create/edit/delete buyers
- Assign risk grades
- Set credit limits
- Search and filter

**API Endpoints**:
- `GET /admin/api/buyers` - List buyers (with search/grade filters)
- `POST /admin/api/buyers` - Create buyer
- `PUT /admin/api/buyers/{id}` - Update buyer
- `DELETE /admin/api/buyers/{id}` - Delete buyer

### Risk Grades Management

**Access**: `/admin/risk-grades`

**Features**:
- Create/edit risk grades
- Set rate adjustments
- Configure funding limits
- Set approval requirements

**API Endpoints**:
- `GET /admin/api/risk-grades` - List risk grades
- `POST /admin/api/risk-grades` - Create risk grade
- `PUT /admin/api/risk-grades/{id}` - Update risk grade
- `DELETE /admin/api/risk-grades/{id}` - Delete risk grade

### User/Role Management

**Access**: `/admin/users`

**Features**:
- Create/edit/delete users
- Assign roles and permissions
- Search users

**API Endpoints**:
- `GET /admin/api/users` - List users
- `GET /admin/api/users/roles` - Get all roles
- `GET /admin/api/users/permissions` - Get all permissions
- `POST /admin/api/users` - Create user
- `PUT /admin/api/users/{id}` - Update user
- `DELETE /admin/api/users/{id}` - Delete user

### Exports

**Invoices Export**:
```
GET /admin/api/exports/invoices?format=excel&status=draft&from_date=2024-01-01&to_date=2024-12-31
```

**Fundings Export**:
```
GET /admin/api/exports/fundings?format=csv&status=executed
```

**Repayments Export**:
```
GET /admin/api/exports/repayments?format=excel&buyer_id=1
```

**Supported Formats**: `excel` (default), `csv`

### Supplier Statements

**Generate Statement**:
```
GET /admin/api/statements/{supplier_id}?from=2024-01-01&to=2024-12-31
```

Returns Excel file with multiple sheets:
- Summary
- Invoices
- Fundings
- Repayments

### Health Checks

**Endpoint**: `GET /health`

Returns JSON with:
- Overall status (healthy/degraded)
- Database check
- Cache check
- Storage check
- Queue check

Use for monitoring/uptime checks.

### Email Notifications

**Scheduled Jobs**:
- **Offer Expiring**: Runs hourly, sends reminders 24h before expiry
- **Repayment Due**: Runs daily, sends reminders 7 days before due date

**Manual Trigger** (for testing):
```bash
php artisan queue:work
# Or in tinker:
dispatch(new \App\Jobs\SendOfferExpiringReminders);
dispatch(new \App\Jobs\SendRepaymentDueReminders);
```

### Data Retention

**Run Monthly** (automated):
```bash
php artisan data:retention
```

**Dry Run** (see what would be deleted):
```bash
php artisan data:retention --dry-run
```

Deletes old:
- Audit events (>7 years)
- Analytics events (>1 year)
- Soft-deleted invoices (>2 years)

### Data Deletion Requests (GDPR/PDPL)

**Process deletion request**:
```bash
php artisan data:delete-request supplier@example.com
```

This will:
- Anonymize supplier data
- Anonymize user data
- Keep transactional data for compliance

## 🔧 Maintenance Commands

```bash
# Run scheduled tasks manually (for testing)
php artisan schedule:test

# Process queue jobs
php artisan queue:work

# Clear cache
php artisan cache:clear

# Check route list
php artisan route:list
```

## 🧪 Testing Checklist

- [ ] Invoice review queue loads and filters work
- [ ] Can claim, approve, reject invoices
- [ ] Buyers CRUD operations work
- [ ] Risk grades CRUD operations work
- [ ] User/role management works
- [ ] Exports generate correct files
- [ ] Statement generation works
- [ ] Health check endpoint responds
- [ ] Email reminders are sent
- [ ] Data retention runs without errors

## 📝 Notes

1. **Frontend Pages**: You'll need to create Vue/Inertia pages for the admin interfaces. See `IMPLEMENTATION_SUMMARY.md` for which pages are needed.

2. **Queue Workers**: Ensure queue workers are running for scheduled emails:
   ```bash
   php artisan queue:work --tries=3
   ```

3. **File Storage**: Export files are stored in `storage/app/public/exports/`. Ensure this directory exists and is writable.

4. **Encryption**: Sensitive fields encryption is configured but needs to be enabled via `ENCRYPT_SENSITIVE_DATA=true` in `.env`.

5. **Backups**: Schedule database backups separately (Laravel doesn't include this by default).

## 🆘 Troubleshooting

**Issue**: Migrations fail
- **Fix**: Check database connection and ensure all previous migrations ran

**Issue**: Exports don't download
- **Fix**: Check `storage/app/public/exports/` directory exists and is writable

**Issue**: Emails not sending
- **Fix**: Check mail configuration in `.env` and ensure queue worker is running

**Issue**: Health check fails
- **Fix**: Check database, cache, and storage connections

**Issue**: Data retention command errors
- **Fix**: Run with `--dry-run` first to see what would be deleted

## 📚 Related Documentation

- `IMPLEMENTATION_SUMMARY.md` - Technical implementation details
- `TEST_CHECKLIST.md` - Feature testing checklist
- `FEATURE_TEST_REPORT.md` - Comprehensive test report

