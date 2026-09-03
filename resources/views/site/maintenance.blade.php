<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance — {{ $siteName }}</title>
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; font-family:"Segoe UI",system-ui,sans-serif;
            background:linear-gradient(160deg,#0f172a,#1e293b); color:#e2e8f0; padding:2rem; }
        .box { max-width:32rem; text-align:center; }
        h1 { font-size:1.75rem; margin:0 0 .75rem; }
        p { opacity:.85; line-height:1.6; }
        a { color:#93c5fd; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{{ $siteName }}</h1>
        <p>{{ $message }}</p>
        <p><a href="{{ url('/login') }}">Admin login</a></p>
    </div>
</body>
</html>
