# Phase 4 — Hardening, Offline Readiness, Compliance

> Raise assurance level and operational resilience to production-grade.

**Prerequisite:** Phase 3 (Scoring + Results) complete.

---

## 1. Outcome

At the end of Phase 4:

1. Proctors can **scan QR codes offline** using a cached roster; scan data syncs to the server when connectivity is restored.
2. Admin accounts require **multi-factor authentication (MFA)** via TOTP (e.g., Google Authenticator).
3. Proctor devices must be **registered and approved** by an admin before scanning is permitted.
4. The audit log is **hash-chained and append-only**, with tooling to verify chain integrity.
5. **Automated backup and restore** procedures are documented and drill-tested.
6. **Monitoring and alerting** are configured for security events and system health.
7. **Data retention and privacy workflows** comply with RA 10173 (Data Privacy Act).
8. An **incident response plan** is documented and rehearsed.

---

## 2. Scope

### In Scope

| Area | Details |
|------|---------|
| **Offline Proctor Scanning** | Before exam day, proctor device downloads encrypted roster (applicant QR payloads, names, photos) for assigned sessions. On scan, QR validated locally against cached data. Scan entries stored locally. When connectivity returns, local entries synced to server with conflict resolution (server timestamp wins for duplicates). Visual indicator: online/offline status. |
| **MFA for Admins** | TOTP-based (RFC 6238). Admin enrolls by scanning QR with authenticator app. Login requires password + TOTP code. Recovery codes generated at enrollment (one-time use). MFA can be enforced per role (admin mandatory; staff/proctor optional). |
| **Device Registration for Proctors** | Proctor's scanning device generates a device fingerprint (user-agent, screen size, or a locally stored token). Admin approves device before proctor can scan from it. Device list viewable and revocable by admin. Unregistered devices blocked from scan endpoint. |
| **Hash-Chained Audit Log** | Each audit record includes `previous_hash` and `current_hash = SHA-256(record_fields + previous_hash)`. Append-only table (no UPDATE or DELETE at DB level — enforced by DB permissions or triggers). Admin can run "Verify Audit Chain" tool that walks the chain and reports breaks. |
| **Audit Log Archival** | Records older than a configurable period (default 1 year) archived to compressed, encrypted files. Archived records remain verifiable (chain continues into archive). Retention: minimum 7 years. |
| **Backup & Recovery** | Automated daily encrypted backups of primary DB + audit DB + file storage. Weekly full backup, daily incremental. Backup integrity verified by checksum. Documented restore procedure. Quarterly restore drill. |
| **Monitoring & Alerting** | Failed login spike detection (> 5 in 10 min from same IP). Unauthorized access attempt alerts (403 events). Audit chain break alert (if detected). Backup failure alert. System resource alerts (disk, CPU, memory). Delivery: email to admin + log. |
| **Intrusion Prevention** | Fail2ban (or equivalent) monitors login failures. Auto-ban IP after configurable threshold (default: 5 failures / 10 min, ban 1 hour). Ban events logged in audit. |
| **Data Retention & Privacy** | Configurable retention period per data category (applicant data, scores, audit logs). Automated anonymization/deletion of expired records. Consent management: record when consent was given, allow withdrawal. Data export: admin can export an applicant's data (right to portability). Data deletion: admin can trigger anonymization of an applicant's records (right to erasure, where legally permissible). |
| **Incident Response Plan** | Documented runbook covering: detection, containment, eradication, recovery, post-incident review. Roles: who is contacted, escalation path. Communication templates for stakeholders. Rehearsed via tabletop exercise. |
| **Performance & Load Testing** | Define targets: concurrent users, response time (< 2s for pages, < 500ms for QR validation). Load test with simulated peak (e.g., 200 concurrent exam-day scans). Identify and resolve bottlenecks. |

### Non-Goals (Deferred / Out of Scope)

- Examinee self-service application portal (future enhancement)
- Native mobile apps (PWA remains the mobile solution)
- Advanced analytics / ML (out of scope)
- Payment processing (out of scope)

---

## 3. User Journeys

### 3.1 Proctor — Offline Scanning

```
Before exam day:
  Proctor logs in on approved device
    -> System prompts: "Download roster for offline use?"
    -> Proctor confirms -> encrypted roster cached on device (via IndexedDB/localStorage)

Exam day (no connectivity):
  Proctor opens scan page
    -> Status indicator shows "OFFLINE"
    -> Proctor scans QR codes
    -> Validation runs locally against cached roster
    -> Scan entries stored in local storage with timestamps
    -> UI shows offline attendance count

Connectivity restored:
  System detects connectivity
    -> Prompts: "42 offline scans ready to sync"
    -> Proctor taps "Sync Now"
    -> Entries uploaded to server
    -> Server reconciles (deduplicates, resolves conflicts)
    -> Confirmation: "42 entries synced, 0 conflicts"
```

### 3.2 Admin — MFA Setup

```
Admin logs in (first time after MFA enforcement)
  -> System redirects to "Set Up Two-Factor Authentication"
  -> Displays QR code for authenticator app
  -> Admin scans with Google Authenticator / Authy
  -> Enters current TOTP code to confirm
  -> System generates 10 recovery codes
  -> Admin saves recovery codes securely
  -> MFA active; subsequent logins require password + TOTP
```

### 3.3 Admin — Device Registration

```
Proctor requests device registration:
  -> Opens scan page on new device
  -> System says "This device is not registered. Request approval."
  -> Proctor enters a note ("Samsung Galaxy Tab A, Room 101 proctor") and submits

Admin reviews device requests:
  -> Opens "Device Management"
  -> Sees pending request with device info and proctor name
  -> Approves device
  -> Proctor can now scan from that device
```

### 3.4 Admin — Audit Chain Verification

```
Admin navigates to "Audit Log" -> "Verify Integrity"
  -> System walks the hash chain from first to last record
  -> Progress bar shows verification progress
  -> Result: "Chain verified: 14,832 records, no breaks detected"
     OR: "CHAIN BREAK at record #8,401 — investigate immediately"
```

### 3.5 Admin — Data Privacy Request

```
Admin receives a data deletion request for applicant "Maria Santos"
  -> Opens "Privacy Management"
  -> Searches for applicant
  -> Reviews associated data (application, scores, documents, audit entries)
  -> Selects "Anonymize Applicant Data"
  -> System replaces personal info with anonymized values
  -> Documents deleted from file storage
  -> Audit log of the anonymization action itself is retained
  -> Confirmation: "Applicant data anonymized. Audit trail preserved."
```

---

## 4. Data Model Changes

| Entity | Changes from Phase 3 |
|--------|---------------------|
| **User** | Add `mfa_secret` (encrypted), `mfa_enabled` (boolean), `recovery_codes` (encrypted JSON array). |
| **Device** (new) | `id`, `user_id`, `fingerprint`, `description`, `status` (pending/approved/revoked), `approved_by`, `approved_at`, `last_used_at`. |
| **AuditLog** | Add `previous_hash` (VARCHAR 64, nullable for first record), `current_hash` (VARCHAR 64). Convert table to append-only (DB-level trigger prevents UPDATE/DELETE). |
| **AuditArchive** (new) | `id`, `archive_file_path`, `record_range_start`, `record_range_end`, `checksum`, `archived_at`. |
| **OfflineScanQueue** (new) | `id`, `device_id`, `exam_assignment_id`, `scanned_at_local`, `qr_payload`, `validation_result`, `synced` (boolean), `synced_at`. |
| **ConsentRecord** (new) | `id`, `applicant_id`, `consent_type`, `granted_at`, `withdrawn_at`, `ip_address`. |
| **DataRetentionPolicy** (new) | `id`, `entity_type`, `retention_days`, `action` (anonymize/delete), `is_active`. |
| **Applicant** | Add `is_anonymized` (boolean, default false). When anonymized, personal fields replaced with placeholders. |

---

## 5. API / UI Surfaces

### New Pages

| Route Pattern | Role | Purpose |
|---------------|------|---------|
| `/admin/mfa/setup` | Admin | MFA enrollment |
| `/login/mfa` | Admin | TOTP entry during login |
| `/admin/devices` | Admin | Device registration management |
| `/admin/audit-log/verify` | Admin | Hash chain verification tool |
| `/admin/audit-log/archives` | Admin | View/download audit archives |
| `/admin/privacy` | Admin | Data subject search, anonymize, export |
| `/admin/monitoring` | Admin | Security alerts dashboard |
| `/admin/backup` | Admin | Backup status and history |
| `/proctor/scan/:session_id/offline` | Proctor | Offline-capable scan page (PWA) |
| `/proctor/sync` | Proctor | Sync offline scan entries |
| `/proctor/devices/register` | Proctor | Request device registration |

### New API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/auth/mfa/setup` | Generate MFA secret + QR |
| POST | `/api/auth/mfa/verify` | Confirm TOTP during setup |
| POST | `/api/auth/mfa/challenge` | Verify TOTP during login |
| POST | `/api/devices/register` | Request device approval |
| PATCH | `/api/devices/:id/approve` | Admin approves device |
| PATCH | `/api/devices/:id/revoke` | Admin revokes device |
| GET | `/api/audit-log/verify` | Run chain verification |
| GET | `/api/audit-log/archives` | List archives |
| POST | `/api/offline-scans/sync` | Upload offline scan batch |
| GET | `/api/roster/:session_id/offline-pack` | Download encrypted roster for offline use |
| POST | `/api/privacy/search` | Search applicant data |
| POST | `/api/privacy/anonymize/:applicant_id` | Anonymize applicant |
| GET | `/api/privacy/export/:applicant_id` | Export applicant data (portability) |
| GET | `/api/monitoring/alerts` | Recent security alerts |
| GET | `/api/backup/status` | Backup health check |

---

## 6. Security Controls (Phase 4 Additions)

| IAS Principle | Control | Implementation |
|---------------|---------|----------------|
| **Confidentiality** | MFA for admins | TOTP required on login; compromised password alone insufficient. |
| **Confidentiality** | Device registration | Only approved devices can submit scans; prevents unauthorized access to scanning endpoint. |
| **Confidentiality** | Encrypted offline roster | Roster cached on device is encrypted; decryption key derived from proctor's session. |
| **Integrity** | Hash-chained audit log | Every record's hash depends on the previous; any tampering breaks the chain. |
| **Integrity** | Append-only audit table | DB triggers prevent UPDATE/DELETE on audit log table. |
| **Integrity** | Backup checksums | Every backup file checksummed; verified before restore. |
| **Availability** | Offline scanning | Exam-day operations continue even during network outages. |
| **Availability** | Automated backups | Daily backups ensure max 24-hour data loss in worst case. |
| **Availability** | Intrusion prevention | Fail2ban blocks brute-force attacks before they exhaust resources. |
| **Accountability** | Audit chain verification | Admin can prove no records have been tampered with. |
| **Accountability** | Privacy action logging | Anonymization and deletion actions are themselves audit-logged. |
| **Non-Repudiation** | Immutable audit chain | Cryptographic proof that log records have not been altered. |

---

## 7. Acceptance Criteria

### Demo Script

1. **Offline scanning:** Proctor downloads roster. Disable network. Scan 5 QR codes — all validated locally. Re-enable network. Sync entries. Verify server has all 5.
2. **MFA enrollment:** Admin sets up MFA. Log out. Log in with password only — blocked. Log in with password + TOTP — succeeds.
3. **MFA recovery:** Use a recovery code instead of TOTP — succeeds. Same recovery code again — rejected (one-time use).
4. **Device registration:** Proctor requests from new device. Admin approves. Proctor can now scan. Admin revokes. Proctor blocked.
5. **Audit chain:** Run verification on clean chain — "no breaks." Manually corrupt a record in test environment — re-run verification — "break detected at record #N."
6. **Backup & restore:** Trigger backup. Verify file exists and checksum matches. Simulate restore to a test environment.
7. **Intrusion prevention:** Attempt 10 failed logins from same IP. Verify ban kicks in after threshold.
8. **Privacy anonymization:** Anonymize a test applicant. Verify personal fields replaced. Verify documents deleted. Verify audit log of the anonymization itself exists.
9. **Data export:** Export applicant data as JSON. Verify completeness.
10. **Load test:** Simulate 200 concurrent QR scans. Verify response time < 500ms (95th percentile).

### Minimum Test Checklist

- [ ] Offline: roster download encrypted; scan works without network; sync uploads correctly.
- [ ] Offline: conflict resolution (duplicate scans) handled gracefully.
- [ ] MFA: TOTP generation and verification correct.
- [ ] MFA: recovery codes work exactly once.
- [ ] MFA: cannot bypass MFA for admin role.
- [ ] Device registration: unregistered device cannot scan.
- [ ] Audit chain: hash computation correct (verify manually for first 10 records).
- [ ] Audit chain: UPDATE/DELETE on audit table rejected at DB level.
- [ ] Audit chain: verification tool detects intentional break.
- [ ] Backup: automated schedule runs; file checksum correct.
- [ ] Backup: restore procedure documented and tested.
- [ ] Fail2ban: ban triggered after threshold; ban expires after duration.
- [ ] Privacy: anonymization replaces all personal fields.
- [ ] Privacy: export includes all applicant-related data.
- [ ] Privacy: consent records created and queryable.
- [ ] Load test: targets met under simulated peak.

---

## 8. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Offline roster data leaked from stolen device | Applicant data exposed | Roster encrypted with session-derived key; key expires with proctor session. Recommend device-level encryption (OS setting). |
| MFA lockout (admin loses phone + recovery codes) | Admin cannot access system | Provide a break-glass procedure: another admin can reset MFA with mandatory audit log and justification. |
| Hash-chain verification is slow on large audit tables | Verification takes too long | Checkpoint system: store verified-up-to marker; verify only new records on subsequent runs. |
| Fail2ban blocks legitimate users (shared IP, e.g., campus NAT) | Users locked out | Use progressive delays instead of hard ban for internal IP ranges. Allow admin to whitelist campus IP blocks. |
| Backup restore has never been tested | Backup is useless when needed | Quarterly restore drill is mandatory (acceptance criterion). |
| Privacy anonymization incomplete (data in logs, caches) | Compliance violation | Anonymization covers all tables referencing applicant_id. Audit log retains action record but not personal data. Cache cleared on anonymization. |
