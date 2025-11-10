# Production Deployment Checklist
## Invoice-Discounting Platform

**Version**: 1.0  
**Date**: October 2025

---

## Pre-Deployment

### Code Readiness
- [ ] All tests passing (`php artisan test`)
- [ ] Code review completed
- [ ] No critical/blocker issues open
- [ ] Documentation updated

### Database
- [ ] Migrations reviewed and tested
- [ ] Backup created
- [ ] Rollback plan documented
- [ ] Indexes optimized

### Configuration
- [ ] Environment variables set
- [ ] `.env.example` updated
- [ ] Debug mode disabled (`APP_DEBUG=false`)
- [ ] Log level appropriate (`LOG_LEVEL=info`)

### Security
- [ ] All dependencies updated
- [ ] Security audit completed
- [ ] API keys/secrets rotated
- [ ] SSL certificates valid

---

## Deployment Steps

### 1. Maintenance Mode
```bash
php artisan down --message="Deploying new version"
```

### 2. Backup
```bash
# Database
php artisan db:backup

# Files
php artisan storage:backup
```

### 3. Code Deployment
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 4. Database
```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
```

### 5. Cache & Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

### 6. Verification
```bash
curl https://app.example.com/up
php artisan tinker # Test database connection
```

### 7. Maintenance Mode Off
```bash
php artisan up
```

---

## Post-Deployment

### Verification
- [ ] Health check endpoint responds
- [ ] Login works
- [ ] Critical flows tested (onboarding, invoice submission)
- [ ] No errors in logs
- [ ] Performance metrics normal

### Monitoring
- [ ] Watch error logs for 30 minutes
- [ ] Monitor queue processing
- [ ] Check database connections
- [ ] Verify email sending

### Communication
- [ ] Notify team of deployment
- [ ] Update status page (if applicable)
- [ ] Document any issues

---

## Rollback Procedure

If critical issue detected:

1. **Enable Maintenance Mode**
   ```bash
   php artisan down
   ```

2. **Rollback Code**
   ```bash
   git checkout <previous-tag>
   ```

3. **Rollback Database** (if needed)
   ```bash
   php artisan migrate:rollback --step=1
   ```

4. **Rebuild & Clear Cache**
   ```bash
   npm run build
   php artisan config:clear
   php artisan route:clear
   ```

5. **Restart Services**
   ```bash
   php artisan queue:restart
   php artisan up
   ```

---

## Production Configuration

### Required Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.example.com

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database
CACHE_DRIVER=redis
SESSION_DRIVER=redis

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...

AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
```

### Server Requirements
- PHP 8.2+
- MySQL 8.0+ / PostgreSQL 13+
- Redis (for cache/queue)
- Node.js 18+ (for build)
- Composer 2.x

### PHP Extensions
- PDO
- OpenSSL
- Mbstring
- XML
- Ctype
- JSON
- BCMath
- Fileinfo

---

## SSL/TLS Configuration

- [ ] SSL certificate installed and valid
- [ ] HTTPS redirect enabled
- [ ] HSTS headers configured
- [ ] TLS 1.2+ enforced

---

## DNS Configuration

- [ ] A/CNAME records pointing to server
- [ ] TTL set appropriately (300s)
- [ ] DNS propagation verified

---

## Backup Configuration

- [ ] Automated daily backups scheduled
- [ ] Backup retention policy configured
- [ ] Backup restoration tested
- [ ] Off-site backup location configured

---

## Monitoring Setup

- [ ] Health check monitoring configured
- [ ] Error tracking (Sentry) integrated
- [ ] Uptime monitoring active
- [ ] Alert thresholds set
- [ ] On-call rotation configured

---

## Security Hardening

- [ ] Firewall rules configured
- [ ] Rate limiting enabled
- [ ] CSRF protection active
- [ ] XSS protection headers set
- [ ] SQL injection prevention verified
- [ ] File upload restrictions enforced

---

## Performance Optimization

- [ ] OPcache enabled
- [ ] Database query caching enabled
- [ ] Static asset CDN configured (if applicable)
- [ ] Image optimization enabled
- [ ] Compression enabled (gzip)

---

## Documentation

- [ ] Runbook accessible to team
- [ ] Incident response procedures documented
- [ ] API documentation published (if applicable)
- [ ] User guides available

---

## Sign-Off

**Deployed By**: _________________  
**Date**: _________________  
**Time**: _________________

**Verified By**: _________________  
**Date**: _________________

**Status**: ⬜ Success / ⬜ Rolled Back / ⬜ Partial

---

**Document Owner**: DevOps Team  
**Review Frequency**: Per deployment











