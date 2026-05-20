<h1>New group</h1>
<form method="post" action="/groups" class="form card-narrow">
    <?= App\Core\Csrf::field() ?>
    <label>
        <span>Name</span>
        <input type="text" name="name" required maxlength="64" pattern="[\p{L}0-9_\-]+">
    </label>
    <label>
        <span>Description (optional)</span>
        <textarea name="description" rows="3" maxlength="500"></textarea>
    </label>
    <button type="submit" class="btn-primary">Create</button>
    <a href="/groups" class="btn-link">Cancel</a>
</form>
