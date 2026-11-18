<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1a1a1a; }

        .page { padding: 20mm 20mm 15mm 20mm; }

        /* Header */
        .header { border-bottom: 2px solid #1e40af; padding-bottom: 10px; margin-bottom: 14px; }
        .header-inner { display: flex; justify-content: space-between; align-items: flex-start; }
        .centre-name { font-size: 16pt; font-weight: bold; color: #1e40af; }
        .centre-contact { font-size: 9pt; color: #555; margin-top: 3px; }
        .doctor-block { text-align: right; }
        .doctor-name { font-size: 13pt; font-weight: bold; }
        .doctor-sub { font-size: 9pt; color: #555; margin-top: 2px; }

        /* Rx banner */
        .rx-banner { display: flex; justify-content: space-between; align-items: center;
                     background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px;
                     padding: 8px 12px; margin-bottom: 12px; }
        .rx-symbol { font-size: 20pt; font-weight: bold; color: #1e40af; }
        .rx-meta { font-size: 9pt; color: #374151; text-align: right; }

        /* Patient */
        .patient-block { border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 12px;
                         margin-bottom: 14px; display: flex; gap: 40px; }
        .patient-block .label { font-size: 8pt; color: #6b7280; text-transform: uppercase; }
        .patient-block .value { font-size: 11pt; font-weight: 600; }

        /* Items */
        .items-title { font-size: 10pt; font-weight: bold; text-transform: uppercase;
                       letter-spacing: 0.05em; color: #374151; margin-bottom: 8px; }
        .item { border-bottom: 1px dashed #d1d5db; padding: 8px 0; }
        .item-name { font-size: 12pt; font-weight: bold; }
        .item-dose { font-size: 10pt; color: #374151; margin-top: 2px; }
        .item-instructions { font-size: 9pt; color: #555; margin-top: 3px; font-style: italic; }
        .item-qty { font-size: 9pt; color: #374151; margin-top: 2px; }

        /* Footer */
        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 10px;
                  display: flex; justify-content: space-between; align-items: flex-end; }
        .signature-block { text-align: center; }
        .signature-line { border-top: 1px solid #374151; width: 160px; margin: 0 auto; }
        .signature-label { font-size: 8pt; color: #6b7280; margin-top: 4px; }
        .allergy-note { font-size: 8pt; color: #dc2626; }
        .disclaimer { font-size: 7pt; color: #9ca3af; margin-top: 10px; text-align: center; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <div class="header-inner">
            <div>
                <div class="centre-name">{{ $prescription->doctor->staffProfile->branch->name ?? config('app.name') }}</div>
                <div class="centre-contact">
                    @if($prescription->doctor->prescription_header)
                        {!! nl2br(e($prescription->doctor->prescription_header)) !!}
                    @endif
                </div>
            </div>
            <div class="doctor-block">
                <div class="doctor-name">Dr. {{ $prescription->doctor->staffProfile->full_name }}</div>
                <div class="doctor-sub">{{ $prescription->doctor->specialty }}</div>
                @if($prescription->doctor->registration_number)
                    <div class="doctor-sub">Reg. No.: {{ $prescription->doctor->registration_number }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Rx Banner -->
    <div class="rx-banner">
        <div class="rx-symbol">℞</div>
        <div class="rx-meta">
            <strong>{{ $prescription->prescription_number }}</strong><br>
            Date: {{ $prescription->issued_at->format('d M Y') }}
        </div>
    </div>

    <!-- Patient -->
    <div class="patient-block">
        <div>
            <div class="label">Patient</div>
            <div class="value">{{ $prescription->patient->full_name }}</div>
        </div>
        @if($prescription->patient->date_of_birth)
        <div>
            <div class="label">DOB</div>
            <div class="value">{{ $prescription->patient->date_of_birth->format('d M Y') }}</div>
        </div>
        @endif
        @if($prescription->patient->patient_number)
        <div>
            <div class="label">Patient No.</div>
            <div class="value">{{ $prescription->patient->patient_number }}</div>
        </div>
        @endif
    </div>

    <!-- Items -->
    <div class="items-title">Prescribed Medications</div>

    @foreach($prescription->items as $item)
    <div class="item">
        <div class="item-name">
            {{ $item->medication_name }}
            @if($item->strength) {{ $item->strength }}@endif
            @if($item->form) ({{ ucfirst($item->form) }})@endif
        </div>
        <div class="item-dose">
            Dose: {{ $item->dose }} &nbsp;|&nbsp; Frequency: {{ $item->frequency }}
            @if($item->duration)
                &nbsp;|&nbsp; Duration: {{ $item->duration_label }}
            @endif
        </div>
        @if($item->quantity)
            <div class="item-qty">Quantity: {{ $item->quantity }}</div>
        @endif
        @if($item->instructions)
            <div class="item-instructions">{{ $item->instructions }}</div>
        @endif
        @if($item->refills_allowed > 0)
            <div class="item-qty">Refills: {{ $item->refills_allowed }}</div>
        @endif
    </div>
    @endforeach

    <!-- Footer -->
    <div class="footer">
        <div>
            @if($prescription->doctor->prescription_footer)
                <div style="font-size:9pt; color:#555;">
                    {!! nl2br(e($prescription->doctor->prescription_footer)) !!}
                </div>
            @endif
        </div>
        <div class="signature-block">
            <div style="height:35px;"></div>
            <div class="signature-line"></div>
            <div class="signature-label">Signature &amp; Stamp</div>
        </div>
    </div>

    <div class="disclaimer">
        This prescription was generated electronically. {{ $prescription->prescription_number }} issued {{ $prescription->issued_at->format('d M Y H:i') }}.
    </div>

</div>
</body>
</html>
