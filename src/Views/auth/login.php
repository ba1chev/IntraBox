<?php /** @var string $title */ ?>
<div class="card card-narrow">
    <h1>Sign in to IntraBox</h1>
    <p class="muted">Anonymous internal mail for your team.</p>

    <form method="post" action="/login" class="form">
        <?= App\Core\Csrf::field() ?>
        <label>
            <span>Username</span>
            <input name="username" type="text" required autofocus autocomplete="username">
        </label>
        <label>
            <span>Password</span>
            <input name="password" type="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn-primary">Sign in</button>
    </form>

    <p class="muted small">
        No account? <a href="/register">Register</a>
    </p>
</div>
