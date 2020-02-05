<section class="intro">
    <div class="intro-body">
        <div class="container">
            <h1>Delete API Key</h1>
            <p class="lead">Are you sure you want to delete API key<br><?= $clientId ;?></p>
            <form method="post" action="">
                <button type="submit" class="btn btn-danger">
                    <?= \Del\Icon::DELETE ?> Delete
                </button>
            </form>

        </div>
    </div>
</section>
