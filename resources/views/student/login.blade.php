<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — E-Jeep MIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <form method="POST" action="{{ route('student.login.post') }}">
        @csrf

        @if ($errors->any())
            <div>{{ $errors->first() }}</div>
        @endif

        <input type="text" name="username" value="{{ old('username') }}" placeholder="Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Login as Student</button>
    </form>
</body>
</html>