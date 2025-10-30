# Non-Functional Requirements (NFR)
## Invoice-Discounting Platform

**Version**: 1.0  
**Date**: October 2025

---

## 1. Performance Requirements

### 1.1 Response Times
- **Page Load**: < 2 seconds (95th percentile)
- **API Endpoints**: < 500ms (95th percentile)
- **File Upload**: < 10 seconds for 10MB files
- **Database Queries**: < 100ms for indexed queries

### 1.2 Throughput
- **Concurrent Users**: 100+ active users
- **Requests per Second**: 50+ RPS sustained
- **File Uploads**: 10+ simultaneous uploads
- **Queue Processing**: Process 1000+ jobs/hour

### 1.3 Scalability
- **Horizontal Scaling**: Application supports multiple workers
- **Database**: Read replicas for reporting queries
- **File Storage**: S3-compatible for production
- **Queue**: Redis queue for high-volume environments

---

## 2. Availability & Reliability

### 2.1 Uptime
- **Target**: 99.5% uptime (≈ 43.8 hours downtime/year)
- **Monitoring**: Health check endpoint `/up`
- **Alerting**: Automatic alerts on downtime

### 2.2 Recovery Time Objectives (RTO)
- **Critical Services**: RTO < 4 hours
- **Non-Critical**: RTO < 24 hours
- **Data Recovery**: RTO < 1 hour (from backups)

### 2.3 Recovery Point Objectives (RPO)
- **Database**: RPO < 1 hour (hourly backups)
- **File Storage**: RPO < 24 hours (daily backups)
- **Audit Logs**: RPO < 15 minutes (continuous replication)

### 2.4 Fault Tolerance
- **Database Failover**: Automatic read replica promotion
- **Queue Failures**: Retry with exponential backoff (max 3 attempts)
- **File Storage**: Graceful degradation if S3 unavailable

---

## 3. Security Requirements

### 3.1 Authentication & Authorization
- **Authentication**: Laravel Sanctum (session-based)
- **Password Policy**: Minimum 8 characters, complexity requirements
- **Session Timeout**: 120 minutes inactivity
- **Multi-Factor Authentication**: Not required for MVP (Phase 2)

### 3.2 Data Protection
- **Encryption at Rest**: Sensitive fields encrypted (bank accounts, PII)
- **Encryption in Transit**: HTTPS enforced (TLS 1.2+)
- **Data Masking**: PII masked in non-admin views
- **Audit Logging**: Immutable logs for all sensitive operations

### 3.3 Vulnerability Management
- **Dependencies**: Regular updates via Composer
- **Security Patches**: Apply within 7 days of release
- **Penetration Testing**: Annual security audit
- **OWASP Top 10**: Address all critical findings

### 3.4 Compliance
- **PDPL/GDPR**: Consent tracking, data retention, right to erasure
- **PCI DSS**: Not applicable (no card data stored)
- **SOC 2**: Target certification in Phase 2

---

## 4. Usability Requirements

### 4.1 User Interface
- **Responsive Design**: Mobile, tablet, desktop support
- **Accessibility**: WCAG 2.1 Level AA compliance (target)
- **Browser Support**: Chrome, Firefox, Safari, Edge (latest 2 versions)
- **RTL Support**: Arabic language with RTL layout

### 4.2 User Experience
- **Onboarding Flow**: < 5 steps to complete KYC
- **Form Validation**: Real-time feedback
- **Error Messages**: Clear, actionable error messages
- **Loading States**: Visual feedback for async operations

### 4.3 Internationalization
- **Languages**: English (default), Arabic
- **Date/Time Formats**: Locale-aware
- **Currency**: Configurable (default: SAR)
- **RTL Layout**: Full support for Arabic

---

## 5. Maintainability Requirements

### 5.1 Code Quality
- **Test Coverage**: > 80% for critical paths
- **Code Reviews**: All changes reviewed before merge
- **Documentation**: API documentation, code comments
- **Static Analysis**: PHPStan level 5 (target)

### 5.2 Monitoring & Observability
- **Application Logs**: Structured logging (JSON)
- **Error Tracking**: Sentry integration (recommended)
- **Performance Monitoring**: APM tool (Phase 2)
- **Correlation IDs**: Request tracing across services

### 5.3 Deployment
- **CI/CD**: Automated tests on push
- **Deployment Frequency**: Weekly releases (target)
- **Rollback**: Support immediate rollback
- **Zero Downtime**: Blue-green deployments (Phase 2)

---

## 6. Data Management

### 6.1 Data Retention
- **Active Records**: Retained indefinitely
- **Soft Deletes**: Retained 7 years
- **Audit Logs**: Retained 10 years (compliance)
- **Backup Retention**: 30 days daily, 12 months monthly

### 6.2 Data Integrity
- **Transactions**: ACID compliance for critical operations
- **Referential Integrity**: Foreign key constraints enforced
- **Data Validation**: Input validation on all endpoints
- **Duplicate Prevention**: Business rules enforced

### 6.3 Backup & Recovery
- **Database Backups**: Daily automated backups
- **File Backups**: Daily S3 snapshots
- **Backup Testing**: Monthly restore tests
- **Disaster Recovery**: Documented DR procedures

---

## 7. Integration Requirements

### 7.1 External Services
- **Email Provider**: SMTP/SES (configurable)
- **SMS Provider**: Placeholder interface (Phase 2)
- **OCR Service**: Adapter pattern (Tesseract/AWS Textract)
- **E-ID/KYC**: Adapter pattern (mock implementation)

### 7.2 API Design
- **RESTful**: Standard HTTP methods
- **Versioning**: `/api/v1/` prefix
- **Rate Limiting**: Per-user and per-endpoint
- **Error Responses**: Consistent JSON error format

### 7.3 Third-Party Dependencies
- **PHP Packages**: Composer-managed
- **JavaScript Packages**: NPM-managed
- **Security**: Monitor for vulnerabilities (Dependabot)

---

## 8. Operational Requirements

### 8.1 Logging
- **Application Logs**: Laravel log files (rotated daily)
- **Audit Logs**: Database table (`audit_events`)
- **Error Logs**: Separate error log channel
- **Access Logs**: Web server logs (Nginx/Apache)

### 8.2 Monitoring
- **Health Checks**: `/up` endpoint
- **Metrics**: Key performance indicators dashboard
- **Alerts**: Email/SMS alerts for critical failures
- **Dashboards**: Admin dashboard with KPIs

### 8.3 Support
- **Documentation**: User guides, API docs
- **Help System**: In-app help tooltips (Phase 2)
- **Support Channels**: Email support (Phase 2: ticket system)

---

## 9. Compliance Requirements

### 9.1 Regulatory
- **PDPL**: Saudi Arabia Personal Data Protection Law
- **GDPR**: EU General Data Protection Regulation (if applicable)
- **Financial Regulations**: Comply with local financial regulations

### 9.2 Data Privacy
- **Consent Tracking**: Explicit consent for data processing
- **Right to Erasure**: Data deletion on request
- **Data Portability**: Export user data on request
- **Privacy Policy**: Clear privacy policy available

---

## 10. Scalability Targets

### 10.1 User Capacity
- **Phase 1 (MVP)**: 500 suppliers, 1000 invoices/month
- **Phase 2**: 5,000 suppliers, 50,000 invoices/month
- **Phase 3**: 50,000 suppliers, 500,000 invoices/month

### 10.2 Data Capacity
- **Database**: 100GB+ supported
- **File Storage**: Unlimited (S3)
- **Audit Logs**: Partitioned tables for performance

---

## 11. Disaster Recovery

### 11.1 Backup Strategy
- **Database**: Daily full backups + transaction logs
- **Files**: Daily incremental backups to S3
- **Configuration**: Version controlled in Git

### 11.2 Recovery Procedures
- **RTO**: < 4 hours for full system recovery
- **RPO**: < 1 hour data loss maximum
- **Testing**: Quarterly DR drills

---

## 12. Change Management

### 12.1 Release Management
- **Versioning**: Semantic versioning (major.minor.patch)
- **Release Notes**: Changelog for each release
- **Feature Flags**: Support feature toggles (Phase 2)
- **Rollback Plan**: Documented for each release

---

**Document Owner**: Engineering Team  
**Last Updated**: October 2025  
**Review Frequency**: Quarterly



