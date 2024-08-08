<?php
/** @var \Bone\OAuth2\Entity\OAuthUser $user; */
$person = $user->getPerson();
?>
<section class="intro">
    <div class="">
        <br>
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <h2>You are logged in as:</h2>
                    <p class="lead">
                        <strong><?= $person->getFullName(); ?></strong><br>
                    </p>
                    <div class="page-scroll">
                        <div class="well overflow" style="color: black;">
                            <form>
                                <input type="hidden" name="continue" value="aye aye"/>
                                <input type="submit" class="btn btn-success" value="Continue"/>
                            </form>
                            <br>
                            <a href="/oauth2/login">Log in as someone else</a>
                            <br>&nbsp;
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
