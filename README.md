# bone-oauth2
Integration of delboy1978uk/user for BoneMvcFramework - WIP

## installation
Install via composer from the root of your Bone Mvc project
```
composer require delboy1978uk/bone-oauth2
```
## configuration
Simply add the Package to Bone's packages config
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
```
Run database migrations to generate the tables
```
migrant diff
migrant migrate
```
#### generate a public and private key
Use openssl to generate a private key.
```
openssl genrsa -out private.key 2048
```
If you want to provide a passphrase for your private key run this command instead:
```
openssl genrsa -passout pass:_passphrase_ -out private.key 2048
```
then extract the public key from the private key:
```
openssl rsa -in private.key -pubout -out public.key
```
or use your passphrase if provided on private key generation:
```
openssl rsa -in private.key -passin pass:_passphrase_ -pubout -out public.key
```
The private key must be kept secret (i.e. out of the web-root of the authorization server). The authorization server also requires the public key.
```
If a passphrase has been used to generate private key it must be provided to the authorization server.
```
The public key should be distributed to any services (for example resource servers) that validate access tokens.
#### generate an encryption key
Run this command and add to your config.
```
vendor/bin/generate-defuse-key
```
### required config values
Keys can be stored out of the config array and fetched as an environment variable for better security, but these are the config settings you need.
```php
<?php

return [
    'oauth2' => [
        '' => '',
    ],   
];
```

## usage
