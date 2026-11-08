<section class="card">
    <p class="eyebrow">SESIÓN SEGURA</p>
    <h2>Hola, <?= h((string) $user['name']) ?></h2>
    <p class="muted">Has iniciado sesión como <?= h((string) $user['email']) ?>.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="logout">
        <button class="button secondary" type="submit">Cerrar sesión</button>
    </form>
</section>
