<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Instructions — {{ $instruction->patient->display_name ?? 'Patient' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; padding: 32px; max-width: 680px; margin: 0 auto; }
        .header { border-bottom: 2px solid #1e40af; padding-bottom: 14px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; color: #1e40af; font-weight: bold; }
        .header .sub { font-size: 12px; color: #555; margin-top: 4px; }
        .patient-box { background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; }
        .patient-box p { font-size: 13px; line-height: 1.6; }
        .patient-box strong { color: #1e3a5f; }
        h2 { font-size: 14px; color: #1e3a5f; margin-bottom: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        ul { list-style: none; padding: 0; }
        ul li { padding: 10px 12px; border-left: 3px solid #1e40af; margin-bottom: 8px; background: #f8fafc; border-radius: 0 4px 4px 0; font-size: 13px; line-height: 1.5; }
        .footer { margin-top: 32px; padding-top: 14px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #888; }
        @media print {
            body { padding: 16px; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Patient Care Instructions</h1>
        <p class="sub">
            Date: {{ $instruction->created_at->format('d F Y') }}
            &nbsp;·&nbsp;
            Visit: {{ $instruction->encounter?->created_at?->format('d M Y') ?? '—' }}
        </p>
    </div>

    <div class="patient-box">
        <p><strong>Patient:</strong> {{ $instruction->patient->display_name ?? '—' }}</p>
        @if($instruction->patient?->date_of_birth)
        <p><strong>DOB:</strong> {{ $instruction->patient->date_of_birth->format('d M Y') }}</p>
        @endif
    </div>

    <h2>Your Doctor's Instructions</h2>
    <ul>
        @foreach($instruction->lines as $line)
            @if(trim($line))
            <li>{{ $line }}</li>
            @endif
        @endforeach
    </ul>

    @if(!empty($followUp))
    <div style="margin-top: 24px; padding: 14px 16px; background: #fffbeb; border: 1px solid #fcd34d; border-left: 4px solid #f59e0b; border-radius: 0 6px 6px 0;">
        <h2 style="color: #92400e; margin-bottom: 8px;">Follow-up Appointment</h2>
        <p style="font-size: 13px; color: #78350f; margin-bottom: 4px;">
            @if($followUp->target_date)
                Please return on or around <strong>{{ $followUp->target_date->format('d F Y') }}</strong>
                (in {{ $followUp->follow_up_days }} days).
            @elseif($followUp->follow_up_days)
                Please return in <strong>{{ $followUp->follow_up_days }} days</strong>.
            @endif
        </p>
        @if($followUp->reason)
        <p style="font-size: 12px; color: #92400e; margin-top: 6px; font-style: italic;">
            {{ $followUp->reason }}
        </p>
        @endif
        @if($followUp->isBooked() && $followUp->appointment)
        <p style="font-size: 12px; color: #1e3a5f; margin-top: 8px; font-weight: bold;">
            Appointment confirmed: {{ $followUp->appointment->appointment_date->format('d F Y') }}
            at {{ $followUp->appointment->appointment_time }}
        </p>
        @else
        <p style="font-size: 11px; color: #92400e; margin-top: 8px;">
            Our reception team will contact you to confirm the exact time.
        </p>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>These instructions are provided by your doctor. If you have any concerns or your condition worsens, please contact the clinic or visit the nearest emergency facility.</p>
    </div>

    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
