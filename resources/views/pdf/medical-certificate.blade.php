<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1a1a1a; }
        .page { padding: 20mm 20mm 15mm 20mm; }

        .header { border-bottom: 2px solid #1e40af; padding-bottom: 10px; margin-bottom: 20px; }
        .centre-name { font-size: 16pt; font-weight: bold; color: #1e40af; }
        .doctor-block { text-align: right; }
        .doctor-name { font-size: 13pt; font-weight: bold; }
        .doctor-sub { font-size: 9pt; color: #555; margin-top: 2px; }
        .header-inner { display: flex; justify-content: space-between; align-items: flex-start; }

        .cert-title { font-size: 15pt; font-weight: bold; text-align: center;
                      text-transform: uppercase; letter-spacing: 0.08em;
                      border: 2px solid #1e40af; padding: 8px; margin-bottom: 20px;
                      color: #1e40af; }

        .cert-number { text-align: right; font-size: 9pt; color: #6b7280; margin-bottom: 16px; }

        .body-text { font-size: 11pt; line-height: 1.8; color: #1a1a1a; }
        .body-text .patient-name { font-weight: bold; font-size: 12pt; }
        .body-text .date-range { font-weight: bold; }

        .details-box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 16px;
                       margin: 16px 0; background: #f9fafb; }
        .details-box .row { margin-bottom: 6px; }
        .details-box .label { font-size: 9pt; text-transform: uppercase; color: #6b7280; }
        .details-box .value { font-size: 11pt; }

        .notes { margin-top: 16px; padding: 10px; background: #fffbeb; border: 1px solid #fde68a;
                 border-radius: 4px; font-size: 10pt; }

        .footer { margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        .signature-block { text-align: center; }
        .signature-line { border-top: 1px solid #374151; width: 180px; margin: 0 auto; }
        .signature-label { font-size: 8pt; color: #6b7280; margin-top: 4px; }
        .issued-block { font-size: 9pt; color: #374151; }

        .disclaimer { font-size: 7pt; color: #9ca3af; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <div class="header-inner">
            <div>
                <div class="centre-name">{{ $certificate->doctor->staffProfile->branch->name ?? config('app.name') }}</div>
                <div style="font-size:9pt;color:#555;margin-top:3px;">Medical Centre</div>
            </div>
            <div class="doctor-block">
                <div class="doctor-name">Dr. {{ $certificate->doctor->staffProfile->full_name }}</div>
                <div class="doctor-sub">{{ $certificate->doctor->specialty }}</div>
                @if($certificate->doctor->registration_number)
                    <div class="doctor-sub">Reg. No.: {{ $certificate->doctor->registration_number }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Certificate title -->
    <div class="cert-title">{{ $certificate->type_label }}</div>

    <!-- Certificate number -->
    <div class="cert-number">Certificate No.: {{ $certificate->certificate_number }}</div>

    <!-- Body -->
    <div class="body-text">
        <p>This is to certify that <span class="patient-name">{{ $certificate->patient->full_name }}</span>,
        @if($certificate->patient->date_of_birth)
            date of birth {{ $certificate->patient->date_of_birth->format('d M Y') }},
        @endif
        was examined by the undersigned physician on {{ $certificate->issued_at->format('d M Y') }}.</p>

        <br>

        @if($certificate->certificate_type === 'sick_leave')
            <p>Based on my examination, I certify that the above-named patient is <strong>unfit for work</strong>
            from <span class="date-range">{{ $certificate->from_date->format('d M Y') }}</span>
            @if($certificate->to_date)
                to <span class="date-range">{{ $certificate->to_date->format('d M Y') }}</span>
                ({{ $certificate->duration_days }} {{ $certificate->duration_days === 1 ? 'day' : 'days' }})
            @endif
            and requires rest and medical attention.</p>
        @elseif($certificate->certificate_type === 'fit_for_work')
            <p>Based on my examination, I certify that the above-named patient is <strong>fit for work</strong>
            with effect from <span class="date-range">{{ $certificate->from_date->format('d M Y') }}</span>.</p>
        @elseif($certificate->certificate_type === 'fitness')
            <p>Based on my examination, I certify that the above-named patient is <strong>medically fit</strong>
            as of <span class="date-range">{{ $certificate->from_date->format('d M Y') }}</span>.</p>
        @else
            <p>Certificate issued for the period
            <span class="date-range">{{ $certificate->from_date->format('d M Y') }}</span>
            @if($certificate->to_date)
                to <span class="date-range">{{ $certificate->to_date->format('d M Y') }}</span>
            @endif.</p>
        @endif
    </div>

    @if($certificate->diagnosis_text || $certificate->notes || $certificate->instructions)
        <div class="details-box">
            @if($certificate->diagnosis_text)
            <div class="row">
                <div class="label">Diagnosis / Condition</div>
                <div class="value">{{ $certificate->diagnosis_text }}</div>
            </div>
            @endif
            @if($certificate->instructions)
            <div class="row">
                <div class="label">Instructions</div>
                <div class="value">{{ $certificate->instructions }}</div>
            </div>
            @endif
        </div>
    @endif

    @if($certificate->notes)
        <div class="notes"><strong>Notes:</strong> {{ $certificate->notes }}</div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div class="issued-block">
            Issued: {{ $certificate->issued_at->format('d M Y') }}<br>
            {{ $certificate->certificate_number }}
        </div>
        <div class="signature-block">
            <div style="height:40px;"></div>
            <div class="signature-line"></div>
            <div class="signature-label">Dr. {{ $certificate->doctor->staffProfile->full_name }}</div>
            @if($certificate->doctor->registration_number)
                <div class="signature-label">Reg. No. {{ $certificate->doctor->registration_number }}</div>
            @endif
        </div>
    </div>

    <div class="disclaimer">
        This certificate was issued electronically. {{ $certificate->certificate_number }} — {{ $certificate->issued_at->format('d M Y H:i') }}.
        This document is valid only with the official stamp and signature of the issuing physician.
    </div>

</div>
</body>
</html>
