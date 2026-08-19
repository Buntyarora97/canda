<?php
/** 500 / maintenance page — must work even when the database is down. */
declare(strict_types=1);
http_response_code(503);
?><!DOCTYPE html>
<html lang="en-CA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>We'll Be Right Back | GIO Mobility Canada</title>
<meta name="robots" content="noindex,nofollow">
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#111315;color:#fff;font-family:system-ui,-apple-system,'Segoe UI',sans-serif;text-align:center;padding:24px}
h1{font-size:clamp(1.6rem,4vw,2.4rem);margin:0 0 12px}
p{color:rgba(255,255,255,.65);max-width:46ch;margin:0 auto 26px;line-height:1.7}
a{color:#FF6B70}
</style>
</head>
<body>
<div>
    <h1>We're doing a quick tune-up.</h1>
    <p>GIO Mobility is temporarily unavailable. Please check back in a few minutes — or reach us by phone at <a href="tel:18559074211">1-855-907-4211</a>.</p>
</div>
</body>
</html>
