# SecureCAT System - Planning & Analysis Phase
## Week 1-2 Deliverable

**Client:** Ilocos Sur Polytechnic State College (ISPSC) - Tagudin Campus  
**Department:** Guidance and Counseling Office  
**Project:** SecureCAT - Secure College Admission Testing System  
**Phase:** Planning & Analysis (Week 1-2)  
**Date:** January 2026

---

## 1. Executive Summary

The Guidance and Counseling Office at ISPSC Tagudin currently manages college entrance examinations using manual processes that are time-consuming, error-prone, and difficult to audit. SecureCAT will modernize this process by providing a secure, efficient system for:

- Managing examination schedules and room assignments
- QR-based identity verification on exam day
- Secure score entry and validation
- Automated result computation and controlled release
- Counselor access to results with evidence-based insights

**Key Benefit:** Transform examination management from paper-based chaos to digital efficiency while maintaining security and accountability.

---

## 2. Stakeholder Analysis

### Primary Stakeholders

**Guidance and Counseling Office (Primary Client)**
- **Key Contact:** [Office Head Name]
- **Primary Needs:**
  - Efficient examination scheduling and room management
  - Accurate score tracking and result computation
  - Tools for counseling students based on test results
  - Reduced manual workload during peak periods
- **Success Criteria:**
  - 70% reduction in manual processing time
  - Zero score entry errors
  - Real-time access to results for counseling

**Registrar's Office (Application System Owner)**
- **Integration Point:** Provides approved examinee data to SecureCAT
- **Needs:** 
  - Seamless data transfer of examinee information
  - Results exported back for admission processing
- **Interface:** API or CSV import/export

**Administrators (Examination Coordinators)**
- **Roles:** Configure schedules, assign rooms, validate scores, release results
- **Needs:** 
  - Clear dashboard showing exam day status
  - Audit trails for all actions
  - Easy score import from OMR scanners

**Proctors (Faculty/Staff)**
- **Roles:** Verify identities on exam day, record attendance
- **Needs:**
  - Fast QR scanning (< 5 seconds per examinee)
  - Mobile-friendly interface
  - Clear alerts for problems (wrong room, invalid QR)

**Examinees (Applicants)**
- **Needs:**
  - Know when and where to take exam
  - Easy access to QR code admission slip
  - View results immediately when released

**Counselors**
- **Needs:**
  - Access to examinee results
  - Subject-level score breakdowns
  - Evidence-based guidance for recommendations

### Secondary Stakeholders

- **IT Department:** System deployment and maintenance
- **Security Office:** Ensure data protection compliance
- **College Deans:** Result data for program planning

---

## 3. Requirements Gathering

### 3.1 Functional Requirements

| ID | Requirement | Priority | Source |
|----|-------------|----------|--------|
| FR-01 | Import examinee data from Registrar system | High | Registrar, G&C |
| FR-02 | Create and manage examination periods (A.Y., semester) | High | G&C Office |
| FR-03 | Define examination schedules with date/time/duration | High | G&C Office |
| FR-04 | Configure examination rooms with capacity limits | High | G&C Office |
| FR-05 | Assign examinees to schedules and rooms | High | G&C Office |
| FR-06 | Generate cryptographically signed QR codes | Critical | Security, G&C |
| FR-07 | Email QR codes to examinees as admission slips | High | Examinees |
| FR-08 | Assign proctors to specific exam sessions/rooms | Medium | Administrators |
| FR-09 | Mobile QR scanning interface for proctors | Critical | Proctors |
| FR-10 | Validate QR signatures and eligibility in real-time | Critical | Security |
| FR-11 | Log attendance with timestamps automatically | High | Auditing |
| FR-12 | Support bulk check-in/check-out for rooms | Medium | Proctors |
| FR-13 | Import OMR scores via CSV file | High | Administrators |
| FR-14 | Dual verification (checker + validator) for scores | High | G&C Policy |
| FR-15 | Configure subject score conversion tables | High | G&C Office |
| FR-16 | Auto-compute converted scores and overall results | Critical | G&C Office |
| FR-17 | Administrator-controlled result release | Critical | G&C Office |
| FR-18 | Examinee self-service result viewing | High | Examinees |
| FR-19 | Counselor access to results with insights | High | Counselors |
| FR-20 | Generate PDF result slips for download | Medium | Examinees |
| FR-21 | Export results to Registrar system | High | Registrar |
| FR-22 | Comprehensive audit logging of all actions | Critical | Security, Compliance |

### 3.2 Non-Functional Requirements

**Security Requirements (IAS Principles)**
- **Confidentiality:** Encryption at rest (AES-256) and in transit (TLS 1.3)
- **Integrity:** Hash-chained audit logs, score checksums, QR signatures
- **Availability:** 99.5% uptime during examination periods
- **Accountability:** All actions logged with user/timestamp/details
- **Non-repudiation:** Cryptographic proof of QR scans and score entries

**Performance Requirements**
- QR validation: < 2 seconds response time
- Score import: Handle 1000+ examinee records in < 30 seconds
- Result computation: Calculate 500+ results in < 1 minute
- Dashboard loading: < 3 seconds for all pages
- Concurrent users: Support 50+ simultaneous users

**Usability Requirements**
- Mobile-responsive design for all interfaces
- Intuitive navigation requiring < 5 minutes training for basic tasks
- Clear error messages in plain English
- Accessible to users with basic computer literacy

**Compliance Requirements**
- Data Privacy Act (RA 10173) compliance
- Secure storage of personal information
- Right to access and correction for examinees
- Data retention for minimum 5 years

---

## 4. Current State Analysis (As-Is Process)

### 4.1 Current Examination Process at ISPSC Tagudin

**Step 1: Receiving Examinee List**
- Registrar provides Excel file of approved applicants
- Manual data entry into examination roster
- **Problems:** Data entry errors, missing records, outdated contact info

**Step 2: Schedule Creation**
- Guidance office manually creates examination schedule
- Room assignments done in Excel spreadsheet
- **Problems:** Room over-capacity, manual counting, conflicts not detected

**Step 3: Admission Slip Distribution**
- Office staff print admission slips with room/schedule info
- Examinees pick up from office or receive via email attachment
- **Problems:** Long queues, lost slips, examinees going to wrong rooms

**Step 4: Exam Day Check-In**
- Proctors manually check IDs against printed roster
- Names checked off with pen
- **Problems:** Slow (5-10 min per examinee), impersonation risk, no timestamp

**Step 5: Score Collection**
- Answer sheets sent to OMR scanning service or manually checked
- Scores returned as Excel file or paper list
- **Problems:** Delays, transcription errors, potential data loss

**Step 6: Score Entry**
- Staff manually enter scores into spreadsheet
- Calculator used for totals and conversion
- **Problems:** Math errors, typos, no verification process

**Step 7: Result Release**
- Guidance counselor reviews results
- Results posted on bulletin board or sent via email
- **Problems:** Privacy issues (public posting), slow distribution, no audit trail

**Step 8: Counseling**
- Counselors manually review score sheets
- No standardized interpretation guidelines
- **Problems:** Inconsistent guidance, time-consuming

### 4.2 Pain Points Summary

| Pain Point | Impact | Frequency |
|------------|--------|-----------|
| Manual data entry errors | High - Wrong rooms, missed exams | Every exam period |
| Slow exam day check-in | High - Delays, examinee frustration | Every exam day |
| Score transcription errors | Critical - Wrong results | 10-15% of scores |
| No audit trail for changes | High - Cannot investigate disputes | When disputes arise |
| Public result posting | Medium - Privacy violations | Every result release |
| Time-consuming result compilation | Medium - Delayed releases | Every exam period |
| Inconsistent counseling | Medium - Unfair guidance | Ongoing |

---

## 5. Proposed Solution (To-Be Process)

### 5.1 SecureCAT Examination Process

**Step 1: Data Import (Automated)**
- API integration or CSV import from Registrar
- Automatic validation and duplicate detection
- **Benefits:** Zero data entry, no errors, instant import

**Step 2: Schedule Configuration (Web Interface)**
- Admin creates exam period, schedules, rooms via dashboard
- System auto-validates capacity constraints
- **Benefits:** No over-capacity, conflict detection, 5-minute setup

**Step 3: Examinee Assignment & QR Generation (Automated)**
- System assigns examinees to rooms (auto or manual)
- QR codes auto-generated with crypto signatures
- Email sent with QR code admission slip
- **Benefits:** Zero manual work, tamper-proof QR, instant distribution

**Step 4: Exam Day Check-In (QR Scan)**
- Proctor scans QR code with phone/tablet
- System validates in < 2 seconds, logs attendance
- **Benefits:** 5 seconds per examinee, prevents impersonation, automatic log

**Step 5: Score Import (OMR Upload)**
- Admin uploads OMR CSV file
- System validates checksum and maps to examinees
- **Benefits:** No manual entry, integrity verification, instant import

**Step 6: Score Verification (Dual Review)**
- Staff checks imported scores (checker role)
- Admin validates final scores (validator role)
- **Benefits:** Separation of duties, audit trail, reduced errors

**Step 7: Result Computation (Automated)**
- System applies conversion tables to raw scores
- Computes subject results and overall totals
- **Benefits:** Zero math errors, instant calculation, consistent formula

**Step 8: Result Release (Controlled)**
- Admin reviews result distribution, clicks "Release"
- Examinees receive email notification
- Results visible in portal (not public bulletin)
- **Benefits:** Privacy protected, instant distribution, access logged

**Step 9: Counseling Support (Evidence-Based)**
- Counselors access results with subject breakdowns
- System shows insights based on score ranges
- **Benefits:** Consistent guidance, evidence-based, time-saving

### 5.2 Benefits Comparison

| Metric | Current (As-Is) | SecureCAT (To-Be) | Improvement |
|--------|----------------|-------------------|-------------|
| Schedule setup time | 2-3 hours | 5-10 minutes | 95% reduction |
| Exam day check-in per examinee | 5-10 minutes | 5 seconds | 98% reduction |
| Score entry time (500 examinees) | 8-10 hours | 5 minutes (import) | 99% reduction |
| Score entry error rate | 10-15% | 0% (automated) | 100% reduction |
| Result computation time | 2-4 hours | 1 minute | 99% reduction |
| Result distribution time | 1-2 days | Instant | Instant |
| Audit trail | None | Complete | 100% coverage |

---

## 6. Risk Assessment (Threat Modeling - STRIDE)

| Threat Type | Specific Threat | Impact | Likelihood | Mitigation |
|-------------|----------------|--------|------------|------------|
| **Spoofing** | Fake QR codes (impersonation) | Critical | Medium | HMAC-SHA256 signatures, server validation |
| **Spoofing** | Stolen user credentials | High | Medium | Password complexity, MFA for admins, session timeout |
| **Tampering** | Score modification after entry | Critical | Low | Audit logs, dual verification, hash-chaining |
| **Tampering** | Altered audit logs | High | Low | Append-only database, hash-chaining |
| **Repudiation** | Proctor denies scanning QR | Medium | Low | Immutable attendance logs with timestamps |
| **Repudiation** | Admin denies releasing results | Medium | Low | Audit log with action details |
| **Information Disclosure** | Unauthorized result access | Critical | Medium | RBAC, encryption, access logging |
| **Information Disclosure** | Examinee data leak | High | Low | Encryption at rest/transit, RBAC |
| **Denial of Service** | System down during exam day | Critical | Low | Offline QR validation, 99.5% uptime SLA |
| **Denial of Service** | Database overload | Medium | Low | Load testing, query optimization |
| **Elevation of Privilege** | Proctor accessing admin functions | High | Low | RBAC enforcement, permission checks |
| **Elevation of Privilege** | SQL injection attack | Critical | Low | Parameterized queries, input validation |

### Risk Prioritization

**Critical Risks (Address First):**
1. Fake QR codes → Cryptographic signatures
2. Unauthorized result access → RBAC + encryption
3. Score tampering → Dual verification + audit logs
4. System downtime on exam day → Offline mode + high availability

**High Risks (Address in Development):**
5. Credential theft → MFA, password policies
6. Data leaks → Encryption, access controls
7. Privilege escalation → Permission checks at every action

---

## 7. Technology Stack Selection

### Frontend
- **HTML5/CSS3/JavaScript (ES6+)**
- **Tailwind CSS** - Rapid responsive design
- **Alpine.js or Vue.js** - Dynamic interactions (lightweight)
- **Rationale:** Modern, mobile-responsive, easy maintenance

### Backend
- **Laravel 10.x (PHP 8.2+)**
- **RESTful API architecture**
- **Rationale:** 
  - Robust security features (CSRF, XSS protection)
  - Built-in authentication/authorization
  - Active community, excellent documentation
  - Eloquent ORM prevents SQL injection

### Database
- **MySQL 8.0+ or PostgreSQL 15+**
- **Redis** - Session storage and caching
- **Rationale:**
  - Reliable, mature relational database
  - Support for encryption at rest
  - Foreign key constraints for data integrity

### Security
- **OpenSSL** - TLS/SSL, cryptographic operations
- **bcrypt** - Password hashing (via Laravel)
- **HMAC-SHA256** - QR code signatures
- **Rationale:** Industry-standard, battle-tested

### QR Code
- **endroid/qr-code (PHP)** - Server-side QR generation
- **HTML5 Camera API** - Client-side scanning
- **Rationale:** No third-party dependencies, full control

### Infrastructure
- **Nginx 1.24+** - Web server
- **Ubuntu Server 22.04 LTS** - Operating system
- **Let's Encrypt** - Free SSL/TLS certificates
- **Fail2ban** - Intrusion prevention
- **Rationale:** Secure, cost-effective, well-documented

---

## 8. High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                       │
│                  (Web Browsers - All Devices)               │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐      │
│  │  Admin   │ │ Proctor  │ │ Examinee │ │Counselor │      │
│  │Dashboard │ │QR Scanner│ │  Portal  │ │Dashboard │      │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘      │
└─────────────────────────────────────────────────────────────┘
                          ▼ HTTPS/TLS 1.3
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                         │
│                    (Laravel Backend)                        │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ Authentication & RBAC                                 │ │
│  │ - User login/session - Role permissions              │ │
│  └───────────────────────────────────────────────────────┘ │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ Business Logic Modules                                │ │
│  │ - Data Import    - Scheduling    - QR Generation     │ │
│  │ - Attendance     - Score Entry   - Result Computation│ │
│  └───────────────────────────────────────────────────────┘ │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ Security Services                                     │ │
│  │ - Audit Logging  - Encryption    - Input Validation  │ │
│  └───────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                          ▼ Encrypted Connection
┌─────────────────────────────────────────────────────────────┐
│                       DATA LAYER                            │
│                 (MySQL/PostgreSQL + Redis)                  │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐       │
│  │  Main DB     │ │  Audit DB    │ │   Redis      │       │
│  │ - Users      │ │ - Login Logs │ │ - Sessions   │       │
│  │ - Examinees  │ │ - Audit Logs │ │ - Cache      │       │
│  │ - Schedules  │ │ (Immutable)  │ │              │       │
│  │ - Scores     │ │              │ │              │       │
│  └──────────────┘ └──────────────┘ └──────────────┘       │
└─────────────────────────────────────────────────────────────┘

External Integration:
┌──────────────────────┐          ┌──────────────────────┐
│  Registrar System    │ ────────▶│     SecureCAT        │
│ (Application Data)   │  API/CSV │ (Examination Mgmt)   │
└──────────────────────┘          └──────────────────────┘
                                           │
                                           │ Export Results
                                           ▼
                                  ┌──────────────────────┐
                                  │  Registrar System    │
                                  │ (Admission Process)  │
                                  └──────────────────────┘
```

---

## 9. User Roles & Permissions Matrix

| Function/Action | Admin | Proctor | Staff (Checker) | Examinee | Counselor |
|-----------------|-------|---------|-----------------|----------|-----------|
| **Data Management** |
| Import examinee data | ✓ | ✗ | ✗ | ✗ | ✗ |
| Create exam periods | ✓ | ✗ | ✗ | ✗ | ✗ |
| Create schedules | ✓ | ✗ | ✗ | ✗ | ✗ |
| Configure rooms | ✓ | ✗ | ✗ | ✗ | ✗ |
| Assign examinees | ✓ | ✗ | ✗ | ✗ | ✗ |
| Assign proctors | ✓ | ✗ | ✗ | ✗ | ✗ |
| **QR & Attendance** |
| Generate QR codes | ✓ (auto) | ✗ | ✗ | ✗ | ✗ |
| Download own QR | ✗ | ✗ | ✗ | ✓ | ✗ |
| Scan QR codes | ✓ | ✓ (assigned) | ✗ | ✗ | ✗ |
| View attendance | ✓ (all) | ✓ (assigned) | ✗ | ✗ | ✗ |
| **Score Management** |
| Import OMR scores | ✓ | ✗ | ✗ | ✗ | ✗ |
| Check scores | ✓ | ✗ | ✓ | ✗ | ✗ |
| Validate scores | ✓ | ✗ | ✗ | ✗ | ✗ |
| Modify scores | ✓ (logged) | ✗ | ✗ | ✗ | ✗ |
| **Results** |
| Compute results | ✓ (auto) | ✗ | ✗ | ✗ | ✗ |
| Release results | ✓ | ✗ | ✗ | ✗ | ✗ |
| View own results | ✗ | ✗ | ✗ | ✓ (published) | ✗ |
| View all results | ✓ | ✗ | ✗ | ✗ | ✓ (published) |
| Access insights | ✓ | ✗ | ✗ | ✗ | ✓ |
| **Security** |
| Access audit logs | ✓ | ✗ | ✗ | ✗ | ✗ |
| Manage users | ✓ | ✗ | ✗ | ✗ | ✗ |
| Configure system | ✓ | ✗ | ✗ | ✗ | ✗ |

---

## 10. Success Metrics & KPIs

### Operational Efficiency
- **Schedule Setup Time:** < 10 minutes (vs. 2-3 hours currently)
- **Exam Day Check-In Speed:** < 5 seconds per examinee (vs. 5-10 minutes)
- **Score Entry Time:** < 5 minutes for bulk import (vs. 8-10 hours)
- **Result Computation:** < 1 minute for 500 examinees (vs. 2-4 hours)

### Quality & Accuracy
- **Score Entry Error Rate:** 0% (vs. 10-15% currently)
- **QR Validation Failure Rate:** < 1% (excluding intentional fakes)
- **System Uptime:** > 99.5% during examination periods

### Security & Compliance
- **Audit Log Completeness:** 100% of administrative actions logged
- **Data Breach Incidents:** 0
- **Unauthorized Access Attempts:** Blocked and logged
- **Compliance Violations:** 0 (Data Privacy Act)

### User Satisfaction
- **Admin Satisfaction:** > 90% (ease of use, time savings)
- **Proctor Satisfaction:** > 85% (QR scanning speed, interface)
- **Examinee Satisfaction:** > 80% (QR delivery, result access)
- **Counselor Satisfaction:** > 85% (insight quality, data access)

---

## 11. Project Constraints

### Time Constraints
- **Development Timeline:** 11 weeks total
- **Go-Live Target:** Before next examination period (A.Y. 2026-2027)
- **Testing Window:** 2 weeks minimum before deployment

### Budget Constraints
- **Hardware:** Use existing ISPSC servers (no new hardware budget)
- **Software:** Open-source stack only (Laravel, MySQL, free tools)
- **Personnel:** Internal development team (no outsourcing budget)

### Technical Constraints
- **Network:** Intermittent internet in some exam rooms → Offline QR validation required
- **Devices:** Proctors use personal phones → Must support Android/iOS browsers
- **Integration:** Registrar system has no API → CSV import/export initially

### Institutional Constraints
- **Data Privacy:** Must comply with RA 10173 (Data Privacy Act)
- **Approval Process:** System changes require Guidance Office approval
- **User Training:** Limited time for training (< 2 hours per role)

---

## 12. Assumptions

1. **Registrar Cooperation:** Registrar's office will provide examinee data in agreed format
2. **Internet Access:** Main admin/server location has stable internet
3. **Proctor Devices:** Proctors have smartphones with working cameras
4. **Email Delivery:** Examinees have working email addresses
5. **User Capability:** Users have basic computer/smartphone literacy
6. **Exam Format:** Paper-based examinations continue (no online testing)
7. **OMR Availability:** OMR scanning service/equipment available for answer sheets

---

## 13. Dependencies

### External Dependencies
1. **Registrar System:** Timely provision of examinee data
2. **Email Service:** Reliable SMTP server for notifications
3. **OMR Service:** Score files provided in compatible format (CSV)
4. **IT Department:** Server provisioning and network access

### Internal Dependencies
1. **Database Design:** Must be completed before development starts
2. **QR Signature Algorithm:** Must be defined before implementation
3. **Score Conversion Rules:** Guidance office must provide conversion tables
4. **User Accounts:** Initial admin account setup

---

## 14. Next Steps (Moving to Design Phase)

### Week 3-6: Design Phase Activities

**You will focus on:**

1. **UI/UX Mockups** (Your Priority)
   - Admin dashboard layout
   - Examination schedule creation screen
   - Room assignment interface
   - Proctor QR scanning mobile interface
   - Score import and validation screens
   - Result release interface
   - Examinee result viewing portal
   - Counselor dashboard with insights

2. **Database Schema Finalization**
   - Already defined in ERD, ready to implement
   - Migration scripts for Laravel

3. **API Specification**
   - Endpoints for each module
   - Request/response formats
   - Authentication requirements

4. **QR Code Design**
   - QR content structure
   - Signature algorithm details
   - Validation logic

### Mockup Checklist (What You Need to Create)

#### Admin Dashboards
- [ ] Login screen
- [ ] Main dashboard (overview)
- [ ] Exam period creation form
- [ ] Schedule creation form
- [ ] Room configuration form
- [ ] Examinee assignment screen (table view)
- [ ] Proctor assignment screen
- [ ] Score import screen
- [ ] Score validation interface
- [ ] Result review screen
- [ ] Result release confirmation
- [ ] Audit log viewer

#### Proctor Interface (Mobile-First)
- [ ] Login screen (mobile)
- [ ] QR scanner interface
- [ ] Attendance success confirmation
- [ ] Error alerts (wrong room, invalid QR)
- [ ] Session roster view
- [ ] Bulk check-in/out buttons

#### Examinee Portal
- [ ] Login/registration screen
- [ ] Examination schedule view
- [ ] QR code download page
- [ ] Result viewing page (before and after release)
- [ ] PDF result slip

#### Counselor Dashboard
- [ ] Login screen
- [ ] Examinee search interface
- [ ] Result details view with subject breakdown
- [ ] Insight recommendations panel

---

## 15. Sign-Off

**Prepared By:**  
[Your Name]  
System Developer

**Reviewed By:**  
[Guidance Office Head]  
ISPSC Tagudin - Guidance and Counseling Office

**Approved By:**  
[Campus Director]  
ISPSC Tagudin Campus

**Date:** January 29, 2026

---

**Appendices:**
- Appendix A: Detailed ERD (securecat-erd.mermaid)
- Appendix B: Full System Proposal (icat-system-updated.md)
- Appendix C: STRIDE Threat Model Details
- Appendix D: Data Privacy Impact Assessment

---

## Quick Reference: Key Decisions Made

✅ **System Scope:** Examination management only (not application processing)  
✅ **Integration:** CSV import from Registrar initially, API later  
✅ **QR Security:** HMAC-SHA256 cryptographic signatures  
✅ **Score Workflow:** Dual verification (checker + validator)  
✅ **Technology:** Laravel + MySQL/PostgreSQL  
✅ **Offline Mode:** QR validation works without internet  
✅ **User Roles:** Admin, Proctor, Staff, Examinee, Counselor  
✅ **Primary Client:** ISPSC Tagudin Guidance & Counseling Office  

**Status:** ✅ Planning & Analysis COMPLETE → Ready for Design Phase (Mockups)
