@extends('layouts.app')

@section('content')
@php
    $dateStr = $session->date instanceof \DateTimeInterface ? $session->date->format('Y-m-d') : ($session->date ?? '—');
    $timeStr = ($session->start_time ?? '') . ' – ' . ($session->end_time ?? '');
    $courseLabel = $session->course ? ($session->course->code . ' — ' . $session->course->name) : '—';
@endphp
<div class="p-6 max-w-2xl" x-data="proctorScan(@js($sessionId), @js($dateStr), @js($timeStr), @js($courseLabel), @js($session->room?->name ?? '—'))" x-init="fetchAttendance()">
    <div class="mb-4">
        <a href="/proctor/sessions" class="link link-hover text-sm">← My sessions</a>
        <h1 class="text-2xl font-bold mt-2">Scan check-in</h1>
        <p class="text-sm text-base-content/70 mt-1" x-text="'Session: ' + courseLabel + ' · ' + dateStr + ' · ' + timeStr + ' · ' + roomName"></p>
        <p class="text-sm font-medium mt-2">Scanned: <span x-text="scannedCount"></span> / <span x-text="totalCount"></span></p>
    </div>

    {{-- Tabs: Camera | Manual --}}
    <div class="tabs tabs-boxed mb-4">
        <button type="button" class="tab" :class="{ 'tab-active': mode === 'manual' }" @click="mode = 'manual'">Manual (paste QR content)</button>
        <button type="button" class="tab" :class="{ 'tab-active': mode === 'camera' }" @click="mode = 'camera'; startCamera()">Camera</button>
    </div>

    {{-- Manual: paste JSON from QR --}}
    <div x-show="mode === 'manual'" class="mb-4">
        <label class="label"><span class="label-text">Paste QR content (from admission slip scan)</span></label>
        <textarea
            x-model="manualInput"
            class="textarea textarea-bordered w-full font-mono text-sm"
            rows="4"
            placeholder='{"qr_payload":"...","qr_signature":"64-char-hex"}'
        ></textarea>
        <button type="button" class="btn btn-primary mt-2" @click="submitScan()" :disabled="submitting">
            <span x-show="!submitting">Validate scan</span>
            <span x-show="submitting">Validating…</span>
        </button>
    </div>

    {{-- Camera: video + result from camera decode --}}
    <div x-show="mode === 'camera'" class="mb-4" x-cloak>
        <div id="qr-reader" class="border border-base-300 rounded-lg overflow-hidden" style="width: 100%; max-width: 400px;"></div>
        <p class="text-sm text-base-content/60 mt-2">Position the admission slip QR inside the frame. Content will be sent automatically.</p>
        <button type="button" class="btn btn-ghost btn-sm mt-2" @click="stopCamera()" x-show="cameraStarted">Stop camera</button>
    </div>

    {{-- Result: pass / fail --}}
    <div x-show="lastResult !== null" class="mt-6" x-cloak>
        <div x-show="lastResult.result === 'valid'" class="alert alert-success shadow-lg">
            <span class="font-semibold">✓ Valid</span>
            <span x-text="lastResult.applicant_name || '—'"></span>
        </div>
        <div x-show="lastResult.result === 'invalid'" class="alert alert-error shadow-lg">
            <span class="font-semibold">✗ Invalid</span>
            <span x-text="lastResult.failure_reason || 'Unknown reason'"></span>
        </div>
        <p class="text-sm text-base-content/60 mt-2">Result is logged. You can scan again.</p>
    </div>

    <template x-if="submitError">
        <div class="alert alert-warning mt-4"><span x-text="submitError"></span></div>
    </template>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function proctorScan(sessionId, dateStr, timeStr, courseLabel, roomName) {
    return {
        sessionId,
        dateStr,
        timeStr,
        courseLabel,
        roomName,
        mode: 'manual',
        manualInput: '',
        submitting: false,
        lastResult: null,
        submitError: null,
        scannedCount: 0,
        totalCount: 0,
        cameraStarted: false,
        html5QrCode: null,

        getHeaders() {
            return {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
        },

        async fetchAttendance() {
            try {
                const res = await fetch('/api/reports/attendance/' + this.sessionId, { credentials: 'include', headers: this.getHeaders() });
                if (res.ok) {
                    const j = await res.json();
                    const data = j.data || [];
                    this.totalCount = data.length;
                    this.scannedCount = data.filter(r => r.scanned_at).length;
                }
            } catch (e) { console.error(e); }
        },

        parseAndSubmit(qrContent) {
            let payload, signature;
            try {
                const raw = typeof qrContent === 'string' ? qrContent.trim() : '';
                if (!raw) return;
                const parsed = JSON.parse(raw);
                payload = parsed.qr_payload;
                signature = parsed.qr_signature;
            } catch (e) {
                this.submitError = 'Invalid QR content: must be JSON with qr_payload and qr_signature.';
                return;
            }
            if (!payload || !signature || signature.length !== 64) {
                this.submitError = 'QR content must include qr_payload and qr_signature (64 hex chars).';
                return;
            }
            this.submitError = null;
            this.doSubmit(payload, signature);
        },

        async doSubmit(qrPayload, qrSignature) {
            this.submitting = true;
            this.lastResult = null;
            try {
                const res = await fetch('/api/scan', {
                    method: 'POST',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify({
                        qr_payload: qrPayload,
                        qr_signature: qrSignature,
                        device_info: navigator.userAgent || null
                    })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.lastResult = { result: data.result, applicant_name: data.applicant_name, failure_reason: data.failure_reason };
                    if (data.result === 'valid') {
                        this.scannedCount++;
                    }
                    this.manualInput = '';
                } else {
                    this.submitError = data.message || 'Request failed.';
                }
            } catch (e) {
                this.submitError = e.message || 'Network error.';
            } finally {
                this.submitting = false;
            }
        },

        submitScan() {
            this.parseAndSubmit(this.manualInput);
        },

        startCamera() {
            if (this.html5QrCode && this.html5QrCode.isScanning()) return;
            if (typeof Html5Qrcode === 'undefined') {
                this.submitError = 'Camera library not loaded. Use Manual and paste QR content.';
                return;
            }
            const self = this;
            this.html5QrCode = new Html5Qrcode('qr-reader');
            this.html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 5, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    self.parseAndSubmit(decodedText);
                    self.stopCamera();
                },
                () => {}
            ).then(() => { self.cameraStarted = true; }).catch(() => {
                self.submitError = 'Could not start camera. Use Manual and paste QR content.';
            });
        },

        stopCamera() {
            if (this.html5QrCode) {
                this.html5QrCode.stop().then(() => { this.cameraStarted = false; }).catch(() => {});
            }
        }
    };
}
</script>
@endsection
