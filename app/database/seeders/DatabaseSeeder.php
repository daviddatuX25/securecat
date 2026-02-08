<?php

namespace Database\Seeders;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use App\Services\QrSigningService;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Local/demo: admin + staff + 2 proctors (password: "password"), 2 applicants (1 assigned with QR, 1 pending),
     * sample periods, courses, rooms, 2 sessions (1 with assignment, 1 without). For testing QR no-regenerate flow.
     * Fully idempotent — safe to run repeatedly without duplicating data.
     */
    public function run(): void
    {
        // --- Users (password: "password") ---
        $passwordHash = Hash::make('password');

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password_hash' => $passwordHash,
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        $staff = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'first_name' => 'Staff',
                'last_name' => 'User',
                'password_hash' => $passwordHash,
                'role' => User::ROLE_STAFF,
                'is_active' => true,
            ]
        );

        $proctor1 = User::firstOrCreate(
            ['email' => 'proctor@example.com'],
            [
                'first_name' => 'Proctor',
                'last_name' => 'One',
                'password_hash' => $passwordHash,
                'role' => User::ROLE_PROCTOR,
                'is_active' => true,
            ]
        );

        $proctor2 = User::firstOrCreate(
            ['email' => 'proctor2@example.com'],
            [
                'first_name' => 'Proctor',
                'last_name' => 'Two',
                'password_hash' => $passwordHash,
                'role' => User::ROLE_PROCTOR,
                'is_active' => true,
            ]
        );

        // --- Admission Periods ---
        $period1 = AdmissionPeriod::firstOrCreate(
            ['name' => '1st Semester AY 2026-2027'],
            [
                'start_date' => '2026-08-01',
                'end_date' => '2026-12-15',
                'status' => AdmissionPeriod::STATUS_ACTIVE,
                'created_by' => $admin->id,
            ]
        );

        AdmissionPeriod::firstOrCreate(
            ['name' => '2nd Semester AY 2026-2027'],
            [
                'start_date' => '2027-01-15',
                'end_date' => '2027-06-15',
                'status' => AdmissionPeriod::STATUS_DRAFT,
                'created_by' => $admin->id,
            ]
        );

        // --- Courses ---
        Course::firstOrCreate(
            ['admission_period_id' => $period1->id, 'code' => 'BSIT'],
            [
                'name' => 'BS Information Technology',
                'description' => 'Bachelor of Science in Information Technology',
            ]
        );

        Course::firstOrCreate(
            ['admission_period_id' => $period1->id, 'code' => 'BSCS'],
            [
                'name' => 'BS Computer Science',
                'description' => 'Bachelor of Science in Computer Science',
            ]
        );

        $course3 = Course::firstOrCreate(
            ['admission_period_id' => $period1->id, 'code' => 'BSDS'],
            [
                'name' => 'BS Data Science',
                'description' => null,
            ]
        );

        // --- Rooms ---
        $room1 = Room::firstOrCreate(
            ['name' => 'Room 101'],
            ['capacity' => 40, 'location_notes' => 'Building A, 1st floor']
        );

        $room2 = Room::firstOrCreate(
            ['name' => 'Room 202'],
            ['capacity' => 30, 'location_notes' => 'Building B']
        );

        // --- Exam Sessions: 1 with assignment (for QR testing), 1 without ---
        $course1 = Course::where('admission_period_id', $period1->id)->where('code', 'BSIT')->first();

        $sessionWithAssignment = ExamSession::firstOrCreate(
            ['course_id' => $course1->id, 'room_id' => $room1->id],
            [
                'proctor_id' => $proctor1->id,
                'date' => now()->addDays(7)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '10:00',
                'status' => 'scheduled',
            ]
        );

        $sessionNoAssignment = ExamSession::firstOrCreate(
            ['course_id' => $course3->id, 'room_id' => $room2->id],
            [
                'proctor_id' => $proctor2->id,
                'date' => now()->addDays(14)->toDateString(),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'status' => 'scheduled',
            ]
        );

        // --- Applicants (2) + Applications: 1 approved & assigned with QR, 1 pending_review ---
        $applicant1 = Applicant::firstOrCreate(
            ['email' => 'applicant1@example.com'],
            [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'contact_number' => '09171234567',
                'date_of_birth' => '2005-03-15',
                'address' => 'Manila',
                'encoded_by' => $staff->id,
            ]
        );

        $applicant2 = Applicant::firstOrCreate(
            ['email' => 'applicant2@example.com'],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'contact_number' => '09187654321',
                'date_of_birth' => '2004-11-22',
                'address' => 'Cebu',
                'encoded_by' => $staff->id,
            ]
        );

        $application1 = Application::firstOrCreate(
            ['applicant_id' => $applicant1->id],
            [
                'course_id' => $course1->id,
                'admission_period_id' => $period1->id,
                'status' => Application::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]
        );

        Application::firstOrCreate(
            ['applicant_id' => $applicant2->id],
            [
                'course_id' => $course1->id,
                'admission_period_id' => $period1->id,
                'status' => Application::STATUS_PENDING_REVIEW,
            ]
        );

        // --- One exam assignment with QR (for session "with assignment") ---
        $qrSigning = app(QrSigningService::class);
        $session = $sessionWithAssignment->fresh(['room']);
        $payload = $qrSigning->buildPayload(
            (int) $application1->applicant_id,
            (int) $session->id,
        );
        $signature = $qrSigning->sign($payload);

        ExamAssignment::firstOrCreate(
            ['application_id' => $application1->id],
            [
                'exam_session_id' => $session->id,
                'seat_number' => 'A-01',
                'qr_payload' => $payload,
                'qr_signature' => $signature,
            ]
        );
    }
}
