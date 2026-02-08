# 05 — Security Controls

> IAS principle mapping, threat model summary, and control catalog.

---

## 1. IAS Principle Mapping

Every security control in SecureCAT traces back to one or more IAS principles. This table provides the complete mapping.

| # | Control | Description | IAS Principle(s) | Phase |
|---|---------|-------------|-------------------|-------|
| SC-01 | RBAC enforcement | Middleware checks role + permission on every request | Confidentiality | 1 |
| SC-02 | Password hashing | bcrypt/argon2 with cost >= 12 | Confidentiality | 1 |
| SC-03 | Session management | Secure tokens in Redis with TTL; configurable timeout | Confidentiality, Availability | 1 |
| SC-04 | HTTPS / TLS | All traffic encrypted; HTTP -> HTTPS redirect | Confidentiality | 1 |
| SC-05 | Encrypted DB connections | PostgreSQL SSL mode enforced | Confidentiality | 1 |
| SC-06 | Data encryption at rest | Sensitive fields encrypted via pgcrypto or app-level | Confidentiality | 1 |
| SC-07 | Input validation | Server-side validation on all inputs (type, length, format) | Integrity | 1 |
| SC-08 | CSRF protection | Token-based CSRF on state-changing requests | Integrity | 1 |
| SC-09 | QR code signing | HMAC-SHA256 signature embedded in QR; verified on scan | Integrity, Non-Repudiation | 1 |
| SC-10 | Foundational audit logging | All mutations logged: user, role, action, entity, timestamp, IP | Accountability | 1 |
| SC-11 | Scan entry logging | Immutable record per QR scan: proctor, time, device, result | Non-Repudiation | 1 |
| SC-12 | Security headers | HSTS, CSP, X-Frame-Options, X-Content-Type-Options | Integrity, Confidentiality | 1 |
| SC-13 | Document access control | Files accessible only to uploader, assigned admin; non-guessable paths | Confidentiality | 2 |
| SC-14 | File type validation | MIME + extension check; only PDF/JPG/PNG accepted | Integrity | 2 |
| SC-15 | Virus scanning | ClamAV on upload; infected files rejected | Integrity, Availability | 2 |
| SC-16 | API authentication | Pre-shared API key or token for intake endpoint; rate-limited | Confidentiality, Availability | 2 |
| SC-17 | Before/after audit diffs | Field-level JSON diffs on update actions | Accountability | 2 |
| SC-18 | Intake source tracking | Application records intake channel (manual/csv/api) | Accountability | 2 |
| SC-19 | Answer key encryption | Encrypted at rest; decrypted only in-memory during scoring | Confidentiality | 3 |
| SC-20 | Score import checksum | SHA-256 computed on upload; stored and verified on subsequent access | Integrity | 3 |
| SC-21 | Score correction justification | Non-empty justification required for any score change | Accountability, Integrity | 3 |
| SC-22 | Pre-release result hiding | Examinee endpoint returns 404 until ResultRelease record exists | Confidentiality | 3 |
| SC-23 | Result access logging | Every view and PDF download logged: accessor, IP, timestamp | Accountability | 3 |
| SC-24 | Score history trail | Separate table tracks all score changes with full context | Accountability | 3 |
| SC-25 | MFA for admins | TOTP (RFC 6238) required on admin login | Confidentiality | 4 |
| SC-26 | Device registration | Proctor devices approved by admin before scanning permitted | Confidentiality | 4 |
| SC-27 | Hash-chained audit log | Each record's hash includes previous record's hash; chain-breakable on tamper | Integrity, Non-Repudiation | 4 |
| SC-28 | Append-only audit table | DB-level trigger prevents UPDATE/DELETE on audit log | Integrity | 4 |
| SC-29 | Encrypted offline roster | Cached roster encrypted; key tied to proctor session | Confidentiality | 4 |
| SC-30 | Intrusion prevention | Fail2ban blocks IPs after repeated failed logins | Availability | 4 |
| SC-31 | Backup verification | Checksummed, encrypted backups; quarterly restore drill | Availability, Integrity | 4 |
| SC-32 | Data anonymization | Personal fields replaced; documents deleted on privacy request | Confidentiality | 4 |
| SC-33 | Consent management | Consent records tracked; withdrawal supported | Confidentiality, Accountability | 4 |

---

## 2. Threat Model Summary (STRIDE)

Based on the STRIDE methodology applied to SecureCAT:

### Spoofing (Identity)

| Threat | Target | Controls |
|--------|--------|----------|
| Fake login credentials | Auth Service | SC-02 (hashing), SC-25 (MFA), SC-30 (Fail2ban) |
| Forged QR code | QR Service | SC-09 (HMAC-SHA256 signature) |
| Impersonation on exam day | Proctor scanning | SC-09 (QR validation), SC-26 (device registration) |
| Unauthorized API access | Intake Gateway | SC-16 (API key/token) |

### Tampering

| Threat | Target | Controls |
|--------|--------|----------|
| Score manipulation | Scoring Module | SC-20 (checksum), SC-21 (justification), SC-24 (history) |
| Audit log modification | Audit Logger | SC-27 (hash-chain), SC-28 (append-only) |
| Application data alteration | Application Management | SC-07 (validation), SC-10/SC-17 (audit), SC-08 (CSRF) |
| Uploaded document replacement | File Storage | SC-13 (access control), SC-15 (virus scan), SC-17 (diff audit) |

### Repudiation

| Threat | Target | Controls |
|--------|--------|----------|
| Admin denies approving application | Approval workflow | SC-10 (audit log with user ID + timestamp) |
| Proctor denies scanning applicant | Scan process | SC-11 (scan entry log with device info) |
| Admin denies releasing results | Result release | SC-23 (ResultRelease record, immutable) |
| Score changer denies modification | Scoring | SC-21 (justification), SC-24 (history) |

### Information Disclosure

| Threat | Target | Controls |
|--------|--------|----------|
| Unauthorized access to exam materials | Answer keys | SC-19 (encryption), SC-01 (RBAC) |
| Result leakage before release | Result data | SC-22 (pre-release hiding), SC-01 (RBAC) |
| Personal data breach | Applicant records | SC-06 (encryption at rest), SC-04 (TLS), SC-01 (RBAC) |
| Document exposure | File Storage | SC-13 (access control, non-guessable paths) |

### Denial of Service

| Threat | Target | Controls |
|--------|--------|----------|
| Brute-force login | Auth Service | SC-30 (Fail2ban), SC-03 (rate limiting) |
| API intake flood | Intake Gateway | SC-16 (rate limiting, API key) |
| Exam-day server failure | QR validation | SC-29 (offline scanning with cached roster) |
| Backup corruption | Data recovery | SC-31 (checksummed backups, restore drills) |

### Elevation of Privilege

| Threat | Target | Controls |
|--------|--------|----------|
| Staff performs admin actions | RBAC Middleware | SC-01 (permission check on every request) |
| Proctor accesses other sessions | Session scoping | SC-01 (RBAC scoped to assigned sessions) |
| Direct object reference (IDOR) | API endpoints | SC-01 (ownership check), SC-07 (input validation) |

---

## 3. Defense-in-Depth Layers

```
Layer 1: Perimeter
  - Firewall (UFW or equivalent)
  - Fail2ban (SC-30)
  - Rate limiting

Layer 2: Transport
  - TLS 1.2+ (SC-04)
  - Encrypted DB connections (SC-05)
  - Security headers (SC-12)

Layer 3: Application
  - Authentication (SC-02, SC-03, SC-25)
  - Authorization / RBAC (SC-01)
  - Input validation (SC-07)
  - CSRF protection (SC-08)

Layer 4: Data
  - Encryption at rest (SC-06, SC-19)
  - Non-guessable file paths (SC-13)
  - Hash-chained audit log (SC-27, SC-28)

Layer 5: Monitoring
  - Audit logging (SC-10, SC-17, SC-23, SC-24)
  - Intrusion alerts
  - Backup verification (SC-31)
```

---

## 4. Compliance Alignment (RA 10173 — Data Privacy Act)

| DPA Requirement | SecureCAT Control |
|----------------|-------------------|
| Lawful processing | Consent captured at encoding (SC-33) |
| Proportionality | Only admission-relevant data collected |
| Data security | Encryption at rest and in transit (SC-04, SC-06); RBAC (SC-01) |
| Access control | RBAC with least privilege (SC-01); MFA for admins (SC-25) |
| Audit trail | Comprehensive logging (SC-10, SC-17, SC-27) |
| Breach notification | Monitoring + incident response plan (Phase 4) |
| Right to access/portability | Data export function (SC-32 / Privacy Manager) |
| Right to erasure | Anonymization function (SC-32 / Privacy Manager) |
| Data retention | Configurable retention policies (Phase 4) |
