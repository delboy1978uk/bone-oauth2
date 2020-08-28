# bone-oauth2
[![Latest Stable Version](https://poser.pugx.org/delboy1978uk/bone-oauth2/v/stable)](https://packagist.org/packages/delboy1978uk/bone-oauth2) [![Total Downloads](https://poser.pugx.org/delboy1978uk/bone-oauth2/downloads)](https://packagist.org/packages/delboy1978uk/bone-oauth2) [![License](https://poser.pugx.org/delboy1978uk/bone-oauth2/license)](https://packagist.org/packages/delboy1978uk/bone-oauth2)<br />
OAuth2 Authorization and Resource Server functionality for Bone MVC Framework

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
Firstly go into the `data/keys` directory.
```
cd data/keys
```
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

If a passphrase has been used to generate private key it must be provided to the authorization server.

The public key should be distributed to any services (for example resource servers) that validate access tokens.
#### generate an encryption key
Go back to the project root.
```
cd ../..
```
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
        'privateKeyPath' => '/path/to/private.key',
        'publicKeyPath' => '/path/to/private.key',
        'encryptionKey' => 'generatedKeyString',
    ],   
];
```
## usage
#### server side
You can create a client using the `vendor/bin/bone` command. You can also create scopes, and grant scopes to clients.

To lock down an endpoint to require an access token, simply add the `ResourceServerMiddleware` to the route or route 
group in your Bone Framework Package class 
```php
$router->map('GET', '/ping', [ExampleController::class, 'pingAction'])->middleware($c->get(ResourceServerMiddleware::class));
```
In your controller, you will have access to the user, which is now an instance of `OAuthUser`. You can also get the 
scopes granted for the request.
```php
    /**
     * @param $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Exception
     */
    public function someAction(ServerRequestInterface $request, array $args) : ResponseInterface
    {
        /** @var \Bone\OAuth2\Entity\OAuthUser $user */
        $user = $request->getAttribute('user');
        
        if (!in_array('email', $request->getAttribute('oauth_scopes'))) {
            throw new Exception('How dare you!', 403);
        }

        return new JsonResponse(['random' => 'data']);
    }
```
#### client side
Clients connect using the standard OAuth2 flow described in RFC6749, the two endpoints in your Bone App are
- /oauth2/authorize
- /oauth2/token
#### site users
Logged in users now have an additional end point which they can go to, `/user/api-keys`, where they can get a new API key, or delete existing ones.
### roadmap
- v1.1.0 Client and Token admin panel
- v1.2.0 Internationalisation