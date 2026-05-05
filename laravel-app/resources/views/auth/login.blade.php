<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — AI Support</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-xl mx-auto mb-3">AI</div>
        <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
        <p class="text-gray-500 text-sm mt-1">Sign in to your support dashboard</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

        @if($errors->any())
            <div class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="you@company.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <input type="password" name="password" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="••••••••">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember"
                       class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="remember" class="text-sm text-gray-600">Remember me</label>
            </div>

            <button type="submit"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors">
                Sign in
            </button>
        </form>

    </div>

    <p class="text-center text-sm text-gray-500 mt-6">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-blue-600 hover:underline font-medium">Create one</a>
    </p>

</div>

</body>
</html>
