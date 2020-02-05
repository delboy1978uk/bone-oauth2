<section class="intro">
    <div class="intro-body">
        <div class="container">
            <h1>Here's your new API key</h1>
            <?= $this->alert(['Do NOT lose your client secret or you will need to create a new client.']) ?>
            <p class="lead">
                Client Id - <?= $clientId ;?><br>
                Client Secret = <?= $clientSecret ;?>
            </p>
            <a href="<?= $this->l() ?>/user/api-keys" class="btn btn-primary">
                <?= \Del\Icon::BACKWARD ?> Back
            </a>
        </div>
    </div>
</section>
