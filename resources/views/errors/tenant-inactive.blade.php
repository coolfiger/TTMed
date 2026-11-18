<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended – TTMed</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f8fafc; }
        .box { text-align: center; max-width: 420px; padding: 2rem; }
        h1 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0 0 .5rem; }
        p { color: #64748b; font-size: .9375rem; margin: 0 0 .25rem; }
        small { color: #94a3b8; font-size: .8125rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Account Unavailable</h1>
        <p>{{ $exception->getMessage() }}</p>
        <p><small>Contact your practice manager or TTMed support.</small></p>
    </div>
</body>
</html>
