<?php /** @var string $title */ ?>
<div class="card card-narrow">
    <h1>Create an account</h1>
    <p class="muted">Join IntraBox — anonymous internal mail for your team.</p>

    <form method="post" action="/register" class="form">
        <?= App\Core\Csrf::field() ?>
        <label>
            <span>Username</span>
            <input name="username" type="text" required autofocus autocomplete="username"
                   pattern="[a-zA-Z0-9_.\-]{3,64}" title="3–64 characters: letters, digits, _ . -">
        </label>
        <label>
            <span>Real name</span>
            <input name="real_name" type="text" required maxlength="128" autocomplete="name">
        </label>
        <label>
            <span>Display alias</span>
            <input name="display_alias" type="text" required maxlength="64"
                   placeholder="shown to others instead of your real name">
        </label>
        <label>
            <span>Email</span>
            <input name="email" type="email" required maxlength="128" autocomplete="email">
        </label>
        <label>
            <span>Password</span>
            <input name="password" type="password" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit" class="btn-primary">Create account</button>
    </form>

    <p class="muted small">
        Already have an account? <a href="/login">Sign in</a>
    </p>
</div>
