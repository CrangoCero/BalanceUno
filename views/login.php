<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BalanceUno</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Materialize CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/custom.css">
</head>

<body class="login-body">
    <main class="login-main">
        <div class="login-box">
            <div class="center-align">
                <div class="login-logo">BalanceUno</div>
                <h5 class="grey-text text-darken-2" style="font-weight: 500; margin-bottom: 30px;">Iniciar Sesión</h5>
            </div>

            <?php if (isset($error)): ?>
                <div class="card-panel red lighten-4 red-text text-darken-4">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="?action=doLogin" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="input-field">
                    <i class="material-icons prefix">person</i>
                    <input id="username" name="username" type="text" required>
                    <label for="username">Usuario</label>
                </div>
                <div class="input-field">
                    <i class="material-icons prefix">lock</i>
                    <input id="password" name="password" type="password" required>
                    <label for="password">Contraseña</label>
                </div>
                <div class="center-align" style="margin-top: 30px;">
                    <button type="submit" class="btn-large waves-effect waves-light teal" style="width: 100%;">
                        Ingresar
                    </button>
                </div>
            </form>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>

</html>