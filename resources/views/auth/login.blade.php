<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | Oryxpace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-dark via-brand to-brand-light text-main">
    <main class="relative flex min-h-screen flex-col items-center justify-center gap-4 overflow-hidden px-6 py-8">
        <div class="pointer-events-none absolute -left-24 -top-24 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-24 h-72 w-72 rounded-full bg-black/10 blur-3xl"></div>

        <form method="POST" action="{{ route('login') }}" class="relative w-full max-w-md rounded-2xl border border-white/70 bg-card p-4 shadow-xl sm:p-6">
            @csrf
            <div class="mb-8 text-center">
                @if (file_exists(public_path('oryxpace_logo_colored.webp')))
                    <img src="{{ asset('oryxpace_logo_colored.webp') }}" alt="Oryxpace Logo" class="mx-auto h-20 w-auto object-contain">
                @else
                    <div class="mx-auto grid h-20 w-20 place-items-center rounded-2xl bg-brand text-3xl font-black text-white shadow-lg shadow-brand/20">O</div>
                @endif
            </div>

            @if ($errors->any())
                <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <span>{{ $errors->first() }}</span>
                    <button type="button" class="float-right ml-2 font-bold" onclick="this.parentElement.remove()" aria-label="Dismiss error">&times;</button>
                </div>
            @endif

            <div class="space-y-5">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-sub" for="email">Email</label>
                    <input class="w-full rounded-xl border border-brand-border bg-card px-4 py-2.5 text-main transition-colors focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand/30" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="pilot@qatarivirtual.com" required autofocus>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-sub" for="password">Password</label>
                    <input class="w-full rounded-xl border border-brand-border bg-card px-4 py-2.5 text-main transition-colors focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand/30" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                </div>
                <button class="w-full rounded-full bg-gradient-to-br from-brand-dark to-brand px-4 py-3 font-semibold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg" type="submit">Sign In</button>
            </div>
        </form>
        <p class="relative max-w-md text-center text-sm leading-relaxed text-white/80">
            <span class="font-semibold text-white">Cannot log in?</span>
            Use the same email and password combination that you use for the Crew Center. Otherwise, open a ticket in the Discord server.
        </p>
    </main>
</body>
</html>
