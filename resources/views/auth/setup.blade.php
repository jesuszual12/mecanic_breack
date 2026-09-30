<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurar taller | mecanic_breack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-layout">
        <section class="auth-brand-panel"><a class="brand auth-brand" href="{{ route('setup') }}"><span class="brand-mark">MB</span><span><strong>MECANIC_BREACK</strong><small>GESTIÓN DE SERVICIO</small></span></a><div class="auth-brand-copy"><span class="auth-kicker">PUESTA EN MARCHA</span><h1>Tu operación,<br>bien cuidada.</h1><p>Configura la cuenta administradora para comenzar a registrar el trabajo del taller.</p></div><span class="auth-brand-foot">PRIMER ACCESO <i>·</i> CONFIGURACIÓN</span></section>
        <section class="auth-form-panel"><div class="auth-form-wrap"><p class="eyebrow">CONFIGURACIÓN INICIAL</p><h2>Crear administrador</h2><p class="auth-intro">Esta pantalla se desactiva después de crear la primera cuenta.</p>
            @if ($errors->any())<div class="flash-error" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('setup.store') }}" class="auth-form">@csrf<label>Nombre<input name="name" value="{{ old('name') }}" autocomplete="name" maxlength="160" required autofocus></label><label>Correo electrónico<input name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="190" required></label><label>Contraseña<input name="password" type="password" autocomplete="new-password" minlength="10" required></label><label>Confirmar contraseña<input name="password_confirmation" type="password" autocomplete="new-password" required></label><button class="button button-primary auth-submit" type="submit">Crear administrador <span>→</span></button></form>
        </div><span class="auth-security">Usa una contraseña única de al menos 10 caracteres</span></section>
    </main>
</body>
</html>