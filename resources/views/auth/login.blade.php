<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar | mecanic_breack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-page">
    <main class="auth-layout">
        <section class="auth-brand-panel"><a class="brand auth-brand" href="{{ route('login') }}"><span
                    class="brand-mark">MB</span><span><strong>MECANIC_BREACK</strong><small>GESTIÓN DE
                        SERVICIO</small></span></a>
            <div class="auth-brand-copy"><span class="auth-kicker">CENTRO DE OPERACIONES</span>
                <h1>El taller,<br>en orden.</h1>
                <p>Control de servicio, vehículos y operación diaria en un solo lugar.</p>
            </div><span class="auth-brand-foot">CONTROL DE SERVICIO <i>·</i> {{ now()->format('Y') }}</span>
        </section>
        <section class="auth-form-panel">
            <div class="auth-form-wrap">
                <p class="eyebrow">ACCESO AL SISTEMA</p>
                <h2>Bienvenido de vuelta</h2>
                <p class="auth-intro">Ingresa con tu cuenta del taller.</p>
                @if ($errors->any())
                    <div class="flash-error" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login.store') }}" class="auth-form">@csrf<label>Correo
                        electrónico<input name="email" type="email" value="{{ old('email') }}"
                            autocomplete="username" required autofocus></label><label>Contraseña<input name="password"
                            type="password" autocomplete="current-password" required></label><label
                        class="remember-control"><input name="remember" type="checkbox" value="1"> Mantener sesión
                        iniciada</label><button class="button button-primary auth-submit" type="submit">Ingresar
                        <span>→</span></button></form>
            </div><span class="auth-security">Acceso privado para personal autorizado</span>
        </section>
    </main>
</body>

</html>
