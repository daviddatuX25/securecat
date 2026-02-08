@extends('layouts.print')

@section('content')
<div class="max-w-md mx-auto border-2 border-base-300 rounded-lg p-6 bg-white">
    <h1 class="text-xl font-bold text-center mb-4">Admission slip</h1>

    <dl class="grid grid-cols-1 gap-2 mb-4">
        <div>
            <dt class="text-sm text-base-content/60">Applicant</dt>
            <dd class="font-semibold">{{ $applicantName }}</dd>
        </div>
        <div>
            <dt class="text-sm text-base-content/60">Course</dt>
            <dd>{{ $courseCode }} — {{ $courseName }}</dd>
        </div>
        <div>
            <dt class="text-sm text-base-content/60">Date & time</dt>
            <dd>{{ $dateStr }} · {{ $timeStr }}</dd>
        </div>
        <div>
            <dt class="text-sm text-base-content/60">Room</dt>
            <dd>{{ $roomName }}</dd>
        </div>
        @if($assignment->seat_number)
        <div>
            <dt class="text-sm text-base-content/60">Seat</dt>
            <dd>{{ $assignment->seat_number }}</dd>
        </div>
        @endif
    </dl>

    <div class="flex justify-center my-4">
        {{-- QR encodes payload + signature for scanner to submit to POST /api/scan --}}
        <img
            src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrContent) }}"
            alt="QR code for verification"
            width="200"
            height="200"
            class="border border-base-300"
        >
    </div>
    <p class="text-xs text-center text-base-content/50">Scan at exam venue for check-in</p>
</div>
@endsection
