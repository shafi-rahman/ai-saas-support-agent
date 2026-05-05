<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account — AI Support</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-xl mx-auto mb-3">AI</div>
        <h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
        <p class="text-gray-500 text-sm mt-1">Set up your AI support agent in seconds</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

        @if($errors->any())
            <div class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Company name</label>
                <input type="text" name="company_name" value="{{ old('company_name') }}" required autofocus
                       class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="Acme Corp">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Your name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="Jane Smith">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="you@company.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <input type="password" name="password" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="Min. 8 characters">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="••••••••">
            </div>

            <button type="submit"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors mt-2">
                Create account
            </button>
        </form>

    </div>

    <p class="text-center text-sm text-gray-500 mt-6">
        Already have an account?
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Sign in</a>
    </p>

</div>

</body>
</html>
