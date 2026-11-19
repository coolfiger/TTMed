<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }
  .page { padding: 32px 40px; }

  /* Header */
  .header { border-bottom: 3px solid #1e3a5f; padding-bottom: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
  .clinic-name { font-size: 18px; font-weight: bold; color: #1e3a5f; }
  .clinic-sub { font-size: 10px; color: #555; margin-top: 3px; }
  .report-title { text-align: right; }
  .report-title h2 { font-size: 14px; color: #1e3a5f; font-weight: bold; }
  .report-title .order-num { font-size: 10px; color: #777; margin-top: 4px; }

  /* Patient info */
  .info-grid { display: flex; gap: 0; margin-bottom: 18px; border: 1px solid #dde; border-radius: 4px; overflow: hidden; }
  .info-cell { flex: 1; padding: 10px 14px; border-right: 1px solid #dde; background: #f8f9fb; }
  .info-cell:last-child { border-right: none; }
  .info-label { font-size: 9px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; margin-bottom: 3px; }
  .info-value { font-size: 11px; font-weight: 600; color: #222; }

  /* Results table */
  .section-title { font-size: 10px; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.8px; font-weight: bold; margin: 18px 0 8px; border-bottom: 1px solid #dde; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #1e3a5f; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 7px 10px; text-align: left; }
  td { padding: 7px 10px; font-size: 11px; border-bottom: 1px solid #eef; }
  tr:last-child td { border-bottom: none; }
  tr.flag-high td { background: #fff5f5; }
  tr.flag-low td { background: #fff8f0; }
  tr.flag-abnormal td { background: #fff8f0; }
  .flag-badge { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
  .flag-high { background: #fee2e2; color: #991b1b; }
  .flag-low { background: #ffedd5; color: #9a3412; }
  .flag-normal { background: #dcfce7; color: #166534; }
  .flag-abnormal { background: #ffedd5; color: #9a3412; }

  /* Footer */
  .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #dde; display: flex; justify-content: space-between; font-size: 9px; color: #888; }
  .signature-block { margin-top: 40px; padding-top: 8px; border-top: 1px solid #333; font-size: 10px; width: 220px; }
  .sig-label { font-size: 9px; color: #666; }
  .disclaimer { font-size: 8.5px; color: #aaa; margin-top: 12px; font-style: italic; }
</style>
</head>
<body>
<div class="page">

  <div class="header">
    <div>
      <div class="clinic-name">{{ $order->branch_name ?? 'Shiva Medical Centre' }}</div>
      <div class="clinic-sub">{{ $order->branch_address ?? '' }}@if($order->branch_phone) &nbsp;|&nbsp; Tel: {{ $order->branch_phone }}@endif</div>
      <div class="clinic-sub" style="margin-top:2px;color:#1e3a5f;">Laboratory Services</div>
    </div>
    <div class="report-title">
      <h2>Laboratory Report</h2>
      <div class="order-num">Order: {{ $order->order_number }}</div>
      <div class="order-num">Printed: {{ $printedAt }}</div>
    </div>
  </div>

  <div class="info-grid">
    <div class="info-cell">
      <div class="info-label">Patient</div>
      <div class="info-value">{{ $order->first_name }} {{ $order->last_name }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Patient #</div>
      <div class="info-value">{{ $order->patient_number }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Date of Birth</div>
      <div class="info-value">{{ $order->date_of_birth ? \Carbon\Carbon::parse($order->date_of_birth)->format('d M Y') : '—' }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Gender</div>
      <div class="info-value">{{ ucfirst($order->gender ?? '—') }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Ordering Physician</div>
      <div class="info-value">{{ $order->doctor_name }}</div>
    </div>
    <div class="info-cell">
      <div class="info-label">Date Ordered</div>
      <div class="info-value">{{ \Carbon\Carbon::parse($order->ordered_at)->format('d M Y') }}</div>
    </div>
  </div>

  <div class="section-title">Results</div>

  <table>
    <thead>
      <tr>
        <th style="width:35%">Test</th>
        <th style="width:18%">Result</th>
        <th style="width:14%">Unit</th>
        <th style="width:20%">Reference Range</th>
        <th style="width:13%">Flag</th>
      </tr>
    </thead>
    <tbody>
      @forelse($results as $r)
      <tr class="{{ in_array($r->flag, ['high','low','abnormal','critical']) ? 'flag-'.$r->flag : '' }}">
        <td><strong>{{ $r->test_name }}</strong></td>
        <td><strong>{{ $r->value }}</strong></td>
        <td>{{ $r->unit }}</td>
        <td>{{ $r->reference_range }}</td>
        <td>
          @if($r->flag && $r->flag !== 'normal')
            <span class="flag-badge flag-{{ $r->flag }}">{{ strtoupper($r->flag) }}</span>
          @else
            <span class="flag-badge flag-normal">Normal</span>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;color:#aaa;">No results recorded.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="disclaimer">
    This report is intended for the named patient and ordering physician only. Results should be interpreted in the context of clinical findings.
    If you have questions about this report please contact the laboratory.
  </div>

  <div class="footer">
    <span>{{ $order->branch_name ?? 'Shiva Medical Centre' }} &mdash; Laboratory Services</span>
    <span>Order {{ $order->order_number }} &mdash; Page 1 of 1</span>
  </div>

</div>
</body>
</html>
