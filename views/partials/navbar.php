    <!-- Barra superior principal -->
    <nav class="primary-color">
        <div class="nav-wrapper nav-app">
            <div class="logo-app">
                <img src="../public/assets/logo.png" alt="BalanceUno" class="logo-img">
                <span class="app-name">Balance Uno</span>
                <!-- <?php if (isset($_SESSION['company_name'])): ?>
                    <span class="company-name-badge hide-on-small-only" style="margin-left: 15px; font-size: 0.9rem; background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 12px; font-weight: 500;"><?= htmlspecialchars($_SESSION['company_name']) ?></span>
                <?php endif; ?> -->
            </div>
            <ul class="right hide-on-med-and-down">
                <li>
                    <!-- <span>
                        <i class="material-icons left">account_circle</i>
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?>
                        <?php if (isset($_SESSION['company_name'])): ?>
                            <span class="grey-text text-lighten-2" style="font-size: 0.8rem; display: block; line-height: 1; margin-top: -12px; margin-left: 30px; text-align: left;"><?= htmlspecialchars($_SESSION['company_name']) ?></span>
                        <?php endif; ?> -->
                    </span>
                </li>
                <li><a href="?action=logout" class="btn waves-effect waves-light secondary-color"><i class="material-icons left">exit_to_app</i>Cerrar Sesión</a></li>
            </ul>
            <!-- Botón logout solo icono para móviles -->
            <ul class="right hide-on-large-only">
                <!-- <?php if (isset($_SESSION['company_name'])): ?>
                    <li style="margin-right: 10px; font-size: 0.8rem;" class="white-text"><?= htmlspecialchars($_SESSION['company_name']) ?></li>
                <?php endif; ?> -->
                <li><a href="?action=logout" class="white-text"><i class="material-icons">exit_to_app</i></a></li>
            </ul>
        </div>
    </nav>

    <!-- Sub-barra de navegación / Título de página -->
    <?php if (isset($pageTitle)): ?>
        <nav class="<?= $navColor ?? 'secondary-color' ?>">
            <div class="nav-wrapper nav-app">
                <a href="?action=dashboard" class="btn-back">
                    <i class="material-icons">arrow_back</i>
                </a>
                <div class="title-app">
                    <i class="material-icons"><?= $pageIcon ?? 'trending_up' ?></i>
                    <h3><?= $pageTitle ?></h3>
                </div>
                <!-- Espaciador para mantener el título centrado -->
                <div style="width: 48px;" class="hide-on-small-only"></div>
            </div>
        </nav>
    <?php endif; ?>