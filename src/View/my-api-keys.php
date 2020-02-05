<section class="intro">
    <div class="intro-body">
        <div class="container">
        <h1>API Keys</h1>
            <?= isset($message) ? $this->alert($message) : null; ?>
            <table class="table table-condensed table-bordered">
                <thead>
                <tr>
                    <th>Client ID</th>
                    <th>Description</th>
                    <th>Grant type</th>
                    <th>Client ID</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php
                /** @var \Bone\OAuth2\Entity\Client $client */
                foreach ($clients as $client) {
                    echo '<tr>
                            <td>' . $client->getName() . '</td>
                            <td>' . $client->getDescription() . '</td>
                            <td>' . $client->getGrantType() . '</td>
                            <td>' . $client->getIdentifier() . '</td>
                            <td><a class="btn btn-xs btn-danger tt" title="Delete" href="/user/api-keys/delete/' . $client->getId() . '">' . \Del\Icon::DELETE . '</a></td>
                          </tr>' ;
                }
                ?>
                </tbody>
            </table>
            <a href="/user/api-keys/add" class=" btn btn-success">
                <?= \Del\Icon::ADD ;?> Get new API key
            </a>
    </div>
    </div>
</section>
