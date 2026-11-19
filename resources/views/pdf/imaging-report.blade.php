<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }
  .page { padding: 32px 40px; }

  .header { border-bottom: 3px solid #1e3a5f; padding-bottom: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
  .clinic-name { font-size: 18px; font-weight: bold; color: #1e3a5f; }
  .clinic-sub { font-size: 10px; color: #555; margin-top: 3px; }
  .report-title { text-align: right; }
  .report-title h2 { font-size: 14px; color: #1e3a5f; font-weight: bold; }
  .report-title .order-num { font-size: 10px; color: #777; margin-top: 4px; }

  .info-grid { display: flex; gap: 0; margin-bottom: 18px; border: 1px solid #dde; border-radius: 4px; overflow: hidden; }
  .info-cell { flex: 1; padding: 10px 14px; border-right: 1px solid #dde; background: #f8f9fb; }
  .info-cell:last-child { border-right: none; }
  .info-label { font-size: 9px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; margin-bottom: 3px; }
  .info-value { font-size: 11px; font-weight: 600; color: #222; }

  .section-title { font-size: 10px; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.8px; font-weight: bold; margin: 20px 0 8px; border-bottom: 1px solid #dde; padding-bottom: 4px; }
  .report-block { background: #f8f9fb; border: 1px solid #dde; border-radius: 4px; padding: 14px 16px; margin-bottom: 14px; line-height: 1.65; }
  .report-block p { margin-bottom: 6px; }
  .report-block p:last-child { margin-bottom: 0; }

  .impression-block { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 14px 16px; margin-bottom: 14px; line-height: 1.65; }
  .impression-block p { margin-bottom: 5px; }

  .recommendation-block { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 14px 16px; line-height: 1.65; }

  .sig-row { display: flex; justify-content: space-between; margin-top: 36px; }
  .sig-block { width: 220px; }
  .sig-line { border-top: 1px solid #333; margin-bottom: 4px; }
  .sig-label { font-size: 9px; color: #666; }

  .footer { margin-top: 20px; padding-top: 12px; border-top: 1px solid #dde; display: flex; justify-content: space-between; font-size: 9px; color: #888; }
  .disclaimer { font-size: 8.5px; color: #aaa; margin-top: 12px; font-style: italic; }
</style>
</head>
<body>
<div class="page">

  <div class="header">
    <div>
      <div class="clinic-name">{{ $report->branch_name ?? 'Shiva Medical Centre' }}</div>
      <div class="clinic-sub">{{ $report->branch_address ?? '' }}@if($report->branch_phone) &nbsp;|&nbsp; Tel: {{ $report->branch_phone }}@endif</div>
      <div class="clinic-sub" style="margin-top:2px;color:#1e3a5f;">Diagnostic Imaging</div>
    </div>
    <div class="report-title">
      <h2>Imaging Report</h2>
      <div class="order-num">Order: {{ $report->order_number }}</div>
      <div class="order-num">Printed: {{ $printedAt }}</div>
    </div>
  </div>

  <div class="info-grid">
    <div class="info-cell">
      <div class="info-label">Patient</div>
      <div class="info-value">{{ $report->first_name }} {{ $report->last_name }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Patient #</div>
      <div class="info-value">{{ $report->patient_number }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Date of Birth</div>
      <div class="info-value">{{ $report->date_of_birth ? \Carbon\Carbon::parse($report->date_of_birth)->format('d M Y') : '—' }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Modality</div>
      <div class="info-value">{{ $report->modality }} &mdash; {{ $report->body_part }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Examination</div>
      <div class="info-value">{{ $report->description }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Report Date</div>
      <div class="info-value">{{ \Carbon\Carbon::parse($report->reported_at)->format('d M Y') }}</div>
    </div>
  </div>

  <div class="section-title">Findings</div>
  <div class="report-block">
    @foreach(explode("\n", $report->findings ?? 'No findings recorded.') as $line)
      @if(trim($line))<p>{{ trim($line) }}</p>@endif
    @endforeach
  </div>

  <div class="section-title">Impression</div>
  <div class="impression-block">
    @foreach(explode("\n", $report->impression ?? 'No impression recorded.') as $line)
      @if(trim($line))<p>{{ trim($line) }}</p>@endif
    @endforeach
  </div>

  @if($report->recommendations)
  <div class="section-title">Recommendations</div>
  <div class="recommendation-block">
    @foreach(explode("\n", $report->recommendations) as $line)
      @if(trim($line))<p>{{ trim($line) }}</p>@endif
    @endforeach
  </div>
  @endif

  <div class="disclaimer">
    This report is generated from clinical data recorded in the TTMed system. It is intended for the named patient and their treating physician only.
    Imaging results should be correlated with clinical findings.
  </div>

  <div class="sig-row">
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-label">Reporting Radiologist: {{ $report->radiologist_name ?? 'Dr. — —' }}</div>
    </div>
    <div class="sig-block" style="text-align:right;">
      <div class="sig-line"></div>
      <div class="sig-label">Date: {{ \Carbon\Carbon::parse($report->reported_at)->format('d M Y') }}</div>
    </div>
  </div>

  <div class="footer">
    <span>{{ $report->branch_name ?? 'Shiva Medical Centre' }} &mdash; Diagnostic Imaging</span>
    <span>Order {{ $report->order_number }} &mdash; Page 1 of 1</span>
  </div>

</div>
</body>
</html>
