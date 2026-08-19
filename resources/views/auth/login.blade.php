<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | Oryxpace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-dark via-brand to-brand-light text-main">
    <main class="relative mx-auto flex min-h-screen max-w-md items-center justify-center overflow-hidden px-5 py-8">
        <div class="pointer-events-none absolute -left-24 -top-24 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-24 h-72 w-72 rounded-full bg-black/10 blur-3xl"></div>

        <form method="POST" action="{{ route('login') }}" class="relative w-full rounded-2xl border border-white/70 bg-card p-8 shadow-xl sm:p-9">
            @csrf
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-brand text-xl font-black text-white shadow-lg shadow-brand/20">O</div>
                <p class="text-sm font-black uppercase tracking-[0.25em] text-brand">Oryxpace</p>
                <p class="mt-2 text-sm text-sub">Sign in to your operations workspace.</p>
            </div>

            @if ($errors->any())
                <div role="alert" class="mb-5 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-800">
                    <span aria-hidden="true" class="text-sm">!</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="space-y-5">
                <div>
                    <label class="field-label" for="email">Email address</label>
                    <input class="input input-bordered w-full bg-slate text-main focus:border-brand focus:outline-brand" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
                <div>
                    <label class="field-label" for="password">Password</label>
                    <input class="input input-bordered w-full bg-slate text-main focus:border-brand focus:outline-brand" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="w-full rounded-xl bg-gradient-to-r from-brand-dark to-brand px-4 py-2.5 font-bold text-white shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg" type="submit">Sign in</button>
            </div>
        </form>
    </main>
</body>
</html>
