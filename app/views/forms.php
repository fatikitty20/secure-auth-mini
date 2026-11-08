<section class="grid">
    <form class="card" method="post" autocomplete="on">
        <p class="eyebrow">VUELVE A TU CUENTA</p>
        <h2>Iniciar sesión</h2>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="login">

        <label for="login-email">Correo</label>
        <input id="login-email" type="email" name="email" autocomplete="username" maxlength="254" required>

        <label for="login-password">Contraseña</label>
        <input id="login-password" type="password" name="password" autocomplete="current-password" maxlength="72" required>

        <button class="button" type="submit">Entrar <span>→</span></button>
    </form>

    <form class="card" method="post" autocomplete="on">
        <p class="eyebrow">EMPIEZA AQUÍ</p>
        <h2>Crear cuenta</h2>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="register">

        <label for="register-name">Nombre</label>
        <input id="register-name" type="text" name="name" autocomplete="name" maxlength="120" required>

        <label for="register-email">Correo</label>
        <input id="register-email" type="email" name="email" autocomplete="email" maxlength="254" required>

        <label for="register-password">Contraseña</label>
        <input id="register-password" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required>
        <p class="hint">Usa al menos 12 caracteres.</p>

        <button class="button secondary" type="submit">Crear cuenta <span>↗</span></button>
    </form>
</section>
