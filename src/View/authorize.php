<?php
/** @var \Bone\OAuth2\Entity\Client $client; */
/** @var \Bone\OAuth2\Entity\Scope[] $scopes; */
?>
<section class="intro">
    <div class="">
        <br>
        <div class="container">
            <div class="row">
                <?= isset($message) ? $this->alert($message): null ?>
                <div class="col-md-8 col-md-offset-2">
                    <img src="/img/skull_and_crossbones.png" />
                    <h1>Do you authorise this app?</h1>
                    <img src="<?= $client->getIcon() ;?>>" alt="<?= $client->getName(); ?>" class="img-rounded" />
                    <p class="lead">
                        <strong><?= $client->getName(); ?></strong><br>
                        <?= $client->getDescription() ;?>
                    </p>
                    <div class="page-scroll">
                        <div class="well overflow" style="color: black;">
                            <ul>
                            <?php
                            foreach ($scopes as $scope) {
                                echo '<li>' . $scope->getDescription() . '</li>';
                            }
                            ?>
                            </ul>
                            <form method="post" action="">
                                <input type="submit" class="btn btn-success" value="Authorize"/>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>