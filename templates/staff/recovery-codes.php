<?php
declare(strict_types=1);
pageStart('Save recovery codes', '../', true, $account);
?>
<section class="hero"><h1>Save your recovery codes</h1></section>
<section class="panel stack narrow">
    <p class="notice" role="status">Your new recovery codes are ready. All previous codes have been replaced.</p>
    <p>Save these codes in a password manager or print them. They are shown only now. Each code works once and can reset your password, so keep them private.</p>
    <ul class="recovery-codes"><?php foreach ($codes as $code): ?><li><code><?= h($code) ?></code></li><?php endforeach; ?></ul>
    <p>If you lose these codes while still signed in, generate a new set from Account.</p>
    <p><a class="button" href="<?= h(staffUrl('account')) ?>">Back to account</a></p>
</section>
<?php pageEnd(); ?>
