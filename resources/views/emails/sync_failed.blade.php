<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; background-color: #f9f9f9; }
        h2 { color: #d9534f; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 15px; font-size: 14px; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <h2> {{ $mailMessage ?? '⚠️ ERPLY to AX Sync Delay Alert' }}</h2>
        <table>
            <tr><th>Current Time</th><td>{{ $currentTime->format('Y-m-d H:i:s') }}</td></tr>
            <tr><th>ERPLY Last Sync</th><td>{{ $erplyTime->format('Y-m-d H:i:s') }}</td></tr>
            @if($axTime)
            <tr><th>AX Last Sync</th><td>{{ $axTime->format('Y-m-d H:i:s') }}</td></tr>
            @endif
            <tr><th>Delay (H:i:s)</th><td>{{ $delay }}</td></tr>
        </table>
        <div class="footer">Please check immediately to resolve the delay.</div>
    </div>
</body>
</html>

