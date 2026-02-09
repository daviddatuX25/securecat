@extends('layouts.print')

@section('content')
<div class="max-w-md mx-auto border-2 border-slate-200 rounded-xl p-6 bg-white shadow-sm">
    <h1 class="text-xl font-bold text-slate-900 text-center mb-4">Admission slip</h1>

    <dl class="grid grid-cols-1 gap-2 mb-4">
        <div>
            <dt class="text-sm text-slate-600">Applicant</dt>
            <dd class="font-semibold text-slate-800">{{ $applicantName }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-600">Course</dt>
            <dd class="text-slate-800">{{ $courseCode }} — {{ $courseName }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-600">Date & time</dt>
            <dd class="text-slate-800">{{ $dateStr }} · {{ $timeStr }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-600">Room</dt>
            <dd class="text-slate-800">{{ $roomName }}</dd>
        </div>
        @if($assignment->seat_number)
        <div>
            <dt class="text-sm text-slate-600">Seat</dt>
            <dd class="text-slate-800">{{ $assignment->seat_number }}</dd>
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
            class="border border-slate-200"
        >
    </div>
    <p class="text-xs text-center text-slate-500">Scan at exam venue for check-in</p>
</div>
@endsection
