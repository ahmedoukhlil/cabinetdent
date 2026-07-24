<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — SysMedical</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f5f7ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 16px;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(30, 58, 138, 0.10);
            overflow: hidden;
        }

        .login-header {
            background: var(--color-primary, #1e3a8a);
            padding: 28px 24px 22px;
            text-align: center;
        }

        @media (min-width: 480px) {
            .login-header { padding: 36px 32px 28px; }
        }

        .login-header img {
            height: 60px;
            width: 60px;
            object-fit: contain;
            background: #fff;
            border-radius: 14px;
            padding: 7px;
            margin: 0 auto 12px;
            display: block;
            box-shadow: 0 2px 12px rgba(0,0,0,0.10);
        }

        @media (min-width: 480px) {
            .login-header img { height: 72px; width: 72px; border-radius: 16px; padding: 8px; margin-bottom: 14px; }
        }

        .login-header h1 {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0 0 4px;
        }

        @media (min-width: 480px) {
            .login-header h1 { font-size: 22px; }
        }

        .login-header p {
            color: rgba(255,255,255,0.70);
            font-size: 11px;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .login-body {
            padding: 24px 20px 20px;
        }

        @media (min-width: 480px) {
            .login-body { padding: 32px 32px 28px; }
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .field-wrap {
            position: relative;
            margin-bottom: 16px;
        }

        .field-wrap i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 13px;
            pointer-events: none;
        }

        .field-wrap input {
            width: 100%;
            padding: 11px 14px 11px 38px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px; /* 16px évite le zoom auto sur iOS */
            color: #111827;
            background: #f9fafb;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        @media (min-width: 480px) {
            .field-wrap input { font-size: 14px; }
        }

        .field-wrap input:focus {
            border-color: var(--color-primary, #1e3a8a);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.08);
        }

        .toggle-password {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            font-size: 13px;
            pointer-events: all;
            padding: 4px;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .remember-row input[type="checkbox"] {
            accent-color: var(--color-primary, #1e3a8a);
            width: 16px;
            height: 16px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .remember-row label {
            font-size: 12px;
            color: #6b7280;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: var(--color-primary, #1e3a8a);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: filter 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            touch-action: manipulation;
        }

        .btn-login:hover { filter: brightness(1.1); }
        .btn-login:active { transform: scale(0.98); }

        .error-box {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #dc2626;
        }

        .login-footer {
            text-align: center;
            padding: 0 0 20px;
            font-size: 11px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="login-card">

        <div class="login-header">
            <img src="/SysMedical.png" alt="SysMedical">
            <h1>SysMedical</h1>
            <p>Votre cabinet digitalisé</p>
        </div>

        <div class="login-body">

            @if($errors->any())
                <div class="error-box">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label class="field-label" for="login">Identifiant</label>
                <div class="field-wrap">
                    <i class="fas fa-user"></i>
                    <input id="login" type="text" name="login"
                           value="{{ old('login') }}"
                           placeholder="Votre identifiant"
                           required autofocus autocomplete="username">
                </div>

                <label class="field-label" for="password">Mot de passe</label>
                <div class="field-wrap">
                    <i class="fas fa-lock"></i>
                    <input id="password" type="password" name="password"
                           placeholder="Votre mot de passe"
                           required autocomplete="current-password">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>

                <div class="remember-row">
                    <input id="remember" type="checkbox" name="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
        </div>

        <div class="login-footer">
            &copy; {{ date('Y') }} SysMedical — Tous droits réservés
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.querySelector('.toggle-password');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
