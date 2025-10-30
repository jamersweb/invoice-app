# Incident Response & Operations Runbook
## Invoice-Discounting Platform

**Version**: 1.0  
**Date**: October 2025

---

## 1. Incident Response Procedures

### 1.1 Incident Classification

#### 🔴 **Critical (P1)**
- Complete system unavailability
- Data breach confirmed
- Payment processing failure
- Unauthorized data access

**Response Time**: Immediate (within 15 minutes)  
**Resolution Target**: < 4 hours

#### 🟠 **High (P2)**
- Partial system outage (major feature)
- Performance degradation (> 5s response)
- Database connectivity issues
- Email/notification failures

**Response Time**: < 1 hour  
**Resolution Target**: < 24 hours

#### 🟡 **Medium (P3)**
- Minor feature broken
- UI issues
- Non-critical errors
- Enhancement requests

**Response Time**: < 4 hours  
**Resolution Target**: Next release

#### 🟢 **Low (P4)**
- Cosmetic issues
- Documentation gaps
- Feature requests

**Response Time**: < 24 hours  
**Resolution Target**: Backlog

---

### 1.2 Incident Response Workflow

1. **Detection**
   - Monitor health check endpoint: `/up`
   - Check application logs: `storage/logs/laravel.log`
   - Review error tracking (if Sentry configured)
   - Monitor database connections

2. **Notification**
   - Alert on-call engineer
   - Create incident ticket
   - Notify stakeholders (if P1/P2)
   - Update status page (if public-facing)

3. **Investigation**
   - Review recent deployments
   - Check system resources (CPU, memory, disk)
   - Review error logs for patterns
   - Check database performance

4. **Containment**
   - If data breach: Immediately revoke access
   - If performance: Enable rate limiting
   - If critical bug: Rollback deployment

5. **Resolution**
   - Apply fix
   - Verify resolution
   - Monitor for recurrence

6. **Post-Incident**
   - Document incident report
   - Conduct post-mortem (P1/P2)
   - Update runbook with lessons learned

---

### 1.3 On-Call Roster

| Week | Primary | Secondary | Escalation |
|------|---------|-----------|------------|
| Week 1 | [Engineer] | [Engineer] | [Tech Lead] |
| Week 2 | [Engineer] | [Engineer] | [Tech Lead] |

**Contact**: [Phone / Slack / PagerDuty]

---

## 2. Data Breach Response

### 2.1 Detection

**Signs of Breach**:
- Unusual access patterns in audit logs
- Unauthorized login attempts
- Unexpected data exports
- Anomalous API calls

**Monitoring**:
- Review `audit_events` table for suspicious activity
- Monitor failed login attempts
- Check file download logs
- Review user permission changes

### 2.2 Immediate Actions

1. **Containment** (within 15 minutes)
   ```bash
   # Revoke affected user access
   php artisan user:suspend {user_id}
   
   # Block suspicious IPs
   # Add to firewall/rate limiter
   
   # Disable affected features if needed
   # Update .env: DISABLE_FEATURE=true
   ```

2. **Assessment** (within 1 hour)
   - Query audit logs to determine scope:
   ```sql
   SELECT * FROM audit_events 
   WHERE actor_id = {breach_user_id} 
   AND created_at >= '{breach_start_time}';
   ```
   - Identify affected data:
     - Which records accessed?
     - Which files downloaded?
     - Which exports generated?

3. **Documentation**
   - Record breach details:
     - Date/time detected
     - Date/time occurred (if known)
     - Affected records count
     - Type of data accessed
     - Root cause (if known)

### 2.3 Notification

**Timeline**: Within 72 hours (GDPR) / ASAP (PDPL)

**Recipients**:
- Affected users (email)
- Data Protection Officer (if exists)
- Regulatory authority (if required)
- Management team

**Template**:
```
Subject: Important: Data Security Notice

Dear [User Name],

We are writing to inform you of a data security incident that may have affected your account.

What happened:
[Brief description]

What information was involved:
[List of data categories]

What we are doing:
[Remediation steps]

What you can do:
- Change your password
- Monitor your account
- Report suspicious activity

We sincerely apologize for this incident.

[Contact Information]
```

### 2.4 Remediation

1. **Patch Vulnerability**
   - If code vulnerability: Deploy fix
   - If misconfiguration: Update config
   - If access control: Revise permissions

2. **Enhanced Security**
   - Review all access controls
   - Implement additional monitoring
   - Conduct security audit

3. **Prevention**
   - Update security procedures
   - Enhance training
   - Implement additional safeguards

---

## 3. Backup & Recovery Procedures

### 3.1 Backup Schedule

| Backup Type | Frequency | Retention | Location |
|-------------|-----------|-----------|----------|
| Database Full | Daily | 30 days | S3/Backup Server |
| Database Incremental | Every 6 hours | 7 days | S3/Backup Server |
| File Storage | Daily | 30 days | S3 Snapshots |
| Configuration | On change | 12 months | Git + Backup Server |
| Audit Logs | Continuous | 10 years | Database Replication |

### 3.2 Backup Execution

#### Automated Daily Backup (Cron Job)
```bash
# Add to crontab (runs 2 AM daily)
0 2 * * * cd /path/to/app && php artisan backup:run
```

#### Manual Backup
```bash
# Database dump
php artisan db:backup --destination=s3 --destination-path=backups/

# Or using mysqldump
mysqldump -u user -p database_name > backup_$(date +%Y%m%d).sql

# Files backup
php artisan storage:backup --disk=s3
```

### 3.3 Recovery Procedures

#### Database Restore
```bash
# Stop application (maintenance mode)
php artisan down

# Restore database
mysql -u user -p database_name < backup_20251029.sql

# Or using Laravel
php artisan db:restore --source=s3 --source-path=backups/backup_20251029.sql

# Verify data
php artisan db:check

# Restart application
php artisan up
```

#### File Restore
```bash
# Restore from S3
aws s3 sync s3://bucket/backups/files/ storage/app/public/

# Or restore specific directory
php artisan storage:restore --source=s3 --path=documents/
```

#### Point-in-Time Recovery
```bash
# Restore to specific date/time
mysql -u user -p database_name < backup_20251029.sql

# Apply binary logs (if enabled)
mysqlbinlog --start-datetime="2025-10-29 10:00:00" \
  --stop-datetime="2025-10-29 12:00:00" binlog.000001 | mysql -u user -p
```

### 3.4 Recovery Testing

**Schedule**: Monthly

**Procedure**:
1. Restore latest backup to test environment
2. Verify data integrity
3. Test application functionality
4. Document results

**Test Checklist**:
- [ ] Database restore successful
- [ ] All tables present
- [ ] Data counts match
- [ ] Files accessible
- [ ] Application starts
- [ ] Critical features work

---

## 4. System Monitoring

### 4.1 Health Checks

**Endpoint**: `GET /up`

**Expected Response**:
```json
{
  "status": "ok",
  "database": "connected",
  "queue": "running",
  "storage": "accessible"
}
```

**Monitoring Frequency**: Every 1 minute

### 4.2 Key Metrics

| Metric | Threshold | Alert |
|--------|-----------|-------|
| Response Time | > 2s (p95) | 🟠 High |
| Error Rate | > 1% | 🟠 High |
| CPU Usage | > 80% | 🟡 Medium |
| Memory Usage | > 85% | 🟡 Medium |
| Disk Usage | > 90% | 🔴 Critical |
| Database Connections | > 80% max | 🟠 High |
| Queue Lag | > 1000 jobs | 🟡 Medium |

### 4.3 Log Monitoring

**Application Logs**: `storage/logs/laravel.log`

**Critical Patterns to Alert**:
- `SQLSTATE[HY000]` (database errors)
- `Failed to open stream` (file errors)
- `429 Too Many Requests` (rate limit)
- `403 Forbidden` (unauthorized access)
- `500 Internal Server Error` (application errors)

### 4.4 Monitoring Tools

**Recommended** (Phase 2):
- **Application**: Sentry / Bugsnag
- **Infrastructure**: Datadog / New Relic
- **Uptime**: Pingdom / UptimeRobot
- **Logs**: Papertrail / Loggly

---

## 5. Deployment Procedures

### 5.1 Pre-Deployment Checklist

- [ ] All tests passing (`php artisan test`)
- [ ] Database migrations reviewed
- [ ] Environment variables updated
- [ ] Configuration cached (`php artisan config:cache`)
- [ ] Route cache cleared (`php artisan route:clear`)
- [ ] Backup created
- [ ] Maintenance mode ready

### 5.2 Deployment Steps

```bash
# 1. Enable maintenance mode
php artisan down --message="Deploying new version"

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services (if applicable)
# systemctl restart php-fpm
# systemctl restart nginx

# 7. Disable maintenance mode
php artisan up

# 8. Verify deployment
curl https://app.example.com/up
```

### 5.3 Rollback Procedure

```bash
# 1. Enable maintenance mode
php artisan down

# 2. Restore previous code
git checkout <previous-commit>
# Or restore from backup

# 3. Rollback migrations (if needed)
php artisan migrate:rollback --step=1

# 4. Rebuild assets
npm run build

# 5. Clear caches
php artisan config:clear
php artisan route:clear

# 6. Restart services

# 7. Disable maintenance mode
php artisan up

# 8. Verify rollback
curl https://app.example.com/up
```

---

## 6. Common Issues & Solutions

### 6.1 Database Connection Issues

**Symptoms**: 500 errors, "SQLSTATE[HY000]" errors

**Investigation**:
```bash
# Check database status
php artisan db:show

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

**Solutions**:
1. Check `.env` database credentials
2. Verify database server is running
3. Check network connectivity
4. Review connection pool settings

---

### 6.2 Queue Not Processing

**Symptoms**: Jobs stuck in queue, no processing

**Investigation**:
```bash
# Check queue status
php artisan queue:failed

# Check worker processes
ps aux | grep queue

# View queue jobs
php artisan queue:work --verbose
```

**Solutions**:
1. Restart queue worker: `php artisan queue:restart`
2. Check failed jobs: `php artisan queue:retry all`
3. Verify queue connection in `.env`
4. Check worker logs

---

### 6.3 File Upload Failures

**Symptoms**: 500 errors on upload, files not saving

**Investigation**:
```bash
# Check disk permissions
ls -la storage/app/public/
chmod -R 775 storage/

# Check disk space
df -h

# Test file storage
php artisan tinker
>>> Storage::disk('public')->put('test.txt', 'test');
```

**Solutions**:
1. Fix permissions: `chmod -R 775 storage bootstrap/cache`
2. Check disk space
3. Verify storage driver in `.env`
4. Check PHP upload limits (`upload_max_filesize`)

---

### 6.4 Performance Degradation

**Symptoms**: Slow page loads, timeouts

**Investigation**:
```bash
# Check slow queries
# Enable MySQL slow query log

# Check application logs for errors
tail -f storage/logs/laravel.log

# Check system resources
top
df -h
free -h
```

**Solutions**:
1. Optimize database queries (add indexes)
2. Enable query caching
3. Review N+1 queries
4. Scale horizontally (add workers)
5. Enable OPcache

---

## 7. Maintenance Windows

**Scheduled**: Monthly (first Sunday, 2 AM - 4 AM)

**Activities**:
- Database maintenance
- Software updates
- Backup verification
- Performance optimization
- Security patches

**Notification**: 48 hours advance notice to users

---

## 8. Emergency Contacts

| Role | Name | Phone | Email | Slack |
|------|------|-------|-------|-------|
| On-Call Engineer | | | | |
| Tech Lead | | | | |
| DevOps | | | | |
| Security Team | | | | |
| Management | | | | |

---

**Document Owner**: DevOps/Engineering Team  
**Review Frequency**: Quarterly or after incidents  
**Last Updated**: October 2025



