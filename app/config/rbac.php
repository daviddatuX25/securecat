<?php

/**
 * RBAC: route/action to allowed roles mapping.
 *
 * Per docs/architecture/08-api-spec-phase1.md, 09-ui-routes-phase1.md.
 * Key format: "METHOD path" (path without leading slash). Use '*' for any authenticated role.
 */

return [
    'api' => [
        // Auth
        'POST api/auth/logout' => ['*'],

        // Scheduling — Admin only
        'GET api/admission-periods' => ['admin'],
        'POST api/admission-periods' => ['admin'],
        'GET api/admission-periods/*' => ['admin'],
        'PATCH api/admission-periods/*' => ['admin'],
        'DELETE api/admission-periods/*' => ['admin'],
        'GET api/courses' => ['admin'],
        'POST api/courses' => ['admin'],
        'GET api/courses/*' => ['admin'],
        'PATCH api/courses/*' => ['admin'],
        'DELETE api/courses/*' => ['admin'],
        'GET api/rooms' => ['admin'],
        'POST api/rooms' => ['admin'],
        'GET api/rooms/*' => ['admin'],
        'PATCH api/rooms/*' => ['admin'],
        'DELETE api/rooms/*' => ['admin'],
        'GET api/exam-sessions' => ['admin', 'proctor'],
        'POST api/exam-sessions' => ['admin'],
        'GET api/exam-sessions/*' => ['admin', 'proctor'],
        'PATCH api/exam-sessions/*' => ['admin'],
        'DELETE api/exam-sessions/*' => ['admin'],

        // Applicants & Applications
        'POST api/applicants' => ['staff'],
        'GET api/applications' => ['admin', 'staff'],
        'GET api/applications/*' => ['admin', 'staff'],
        'POST api/applications/*/approve' => ['admin'],
        'POST api/applications/*/reject' => ['admin'],
        'POST api/applications/*/request-revision' => ['admin'],
        'POST api/applications/*/assign' => ['admin'],
        'PATCH api/applications/*' => ['staff'],
        'GET api/exam-assignments/*' => ['admin', 'staff'],

        // Scanning
        'POST api/scan' => ['proctor'],

        // Reports
        'GET api/reports/roster/*' => ['admin', 'proctor'],
        'GET api/reports/attendance/*' => ['admin', 'proctor'],
        'GET api/dashboard' => ['admin'],

        // Audit
        'GET api/audit-log' => ['admin'],
    ],

    'web' => [
        'GET login' => null, // public
        'POST login' => null, // public
        'POST logout' => ['*'], // any authenticated
        'GET *' => ['*'],    // root / (path "" normalizes to *)
        'GET admin/dashboard' => ['admin'],
        'GET admin/periods' => ['admin'],
        'GET admin/periods/new' => ['admin'],
        'GET admin/periods/*/edit' => ['admin'],
        'GET admin/courses' => ['admin'],
        'GET admin/courses/new' => ['admin'],
        'GET admin/courses/*/edit' => ['admin'],
        'GET admin/rooms' => ['admin'],
        'GET admin/rooms/new' => ['admin'],
        'GET admin/rooms/*/edit' => ['admin'],
        'GET admin/sessions' => ['admin'],
        'GET admin/sessions/new' => ['admin'],
        'GET admin/sessions/*/edit' => ['admin'],
        'GET admin/applications' => ['admin'],
        'GET admin/applications/*' => ['admin'],
        'GET admin/reports/roster' => ['admin'],
        'GET admin/reports/attendance' => ['admin'],
        'GET admin/*' => ['admin'],
        'GET staff/home' => ['staff'],
        'GET staff/encode' => ['staff'],
        'GET staff/applications' => ['staff'],
        'GET staff/applications/*' => ['staff'],
        'GET staff/*' => ['staff'],
        'GET staff/applications/*/revise' => ['staff'],
        'GET proctor/sessions' => ['proctor'],
        'GET proctor/scan/*' => ['proctor'],
        'GET proctor/attendance/*' => ['proctor'],
        'GET proctor/home' => ['proctor'],
        'GET proctor/roster' => ['proctor'],
        'GET print/admission-slip/*' => ['admin', 'staff'],
    ],
];
