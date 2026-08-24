<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="0;url={{ url('/login') }}">
    <title>{{ config('app.name', 'Akwad System') }}</title>
    <script>window.location.replace(@json(url('/login')));</script>
</head>
<body>
    <p><a href="{{ url('/login') }}">تسجيل الدخول</a></p>
</body>
</html>
