<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In — Matrimony Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-slate-800">
    <div class="w-full max-w-md">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-2xl bg-rose-600 flex items-center justify-center text-white font-black text-3xl mx-auto shadow-xl shadow-rose-600/30 mb-3">
                M
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Admin Portal</h1>
            <p class="text-slate-400 text-sm mt-1">Matrimony Platform Moderation & Management</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border border-slate-100">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm border border-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-3 rounded-lg bg-rose-50 text-rose-800 text-sm border border-rose-200">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="login" class="block text-sm font-semibold text-slate-700 mb-1.5">Email or Phone</label>
                    <input type="text"
                           id="login"
                           name="login"
                           value="{{ old('login', 'admin@matrimony.test') }}"
                           required
                           autofocus
                           placeholder="admin@matrimony.test or 01700000000"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-sm transition-shadow @error('login') border-rose-500 ring-1 ring-rose-500 @enderror">
                    @error('login')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                    </div>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           value="password"
                           placeholder="••••••••"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-sm transition-shadow @error('password') border-rose-500 ring-1 ring-rose-500 @enderror">
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-slate-600 select-none cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4">
                        <span>Remember this device</span>
                    </label>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-semibold text-sm shadow-lg shadow-rose-600/30 transition-all flex items-center justify-center gap-2">
                    Sign in to Admin
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <span class="text-xs text-slate-400">Default Staff: <code>admin@matrimony.test</code> / <code>password</code></span>
            </div>
        </div>
    </div>
</body>
</html>
