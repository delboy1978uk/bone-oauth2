# bone-oauth2
Integration of delboy1978uk/user for BoneMvcFramework - WIP

##Usage
Simply add the Package to Bone's module config
```php
<?php

// use statements here
use Bone\OAuth2\BoneOAuth2Package;
use Del\UserPackage;

return [
    'packages' => [
        // packages here (order is important)...,
        UserPackage::class,
        BoneOAuth2Package::class,
    ],
    // ...
];
