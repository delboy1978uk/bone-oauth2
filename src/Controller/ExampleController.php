<?php

namespace Bone\OAuth2\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class ExampleController
{
    /**
     *  This is an example callback your site calling this server would use
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     */
    public function callbackAction(ServerRequestInterface $request, array $args) : ResponseInterface
    {
        // get the code and state from the request
        $params = $request->getQueryParams();
        $code = $params['code'];
        $state = $params['state'];

        // at this point, you would check the state sent in the request is the same as the session stored value
        // if not, throw a 400

        // Now, make a call to the access token endpoint, sending the code sent by the server
        $clientID = '05c99d2eb8fc4a8019d06a21097f3c46';
        $redirectUri = 'https://awesome.scot/oauth2/callback';
        $clientSecret = 'JDJ5JDEwJGVkdlMyNW9xOFlTeG1YMGJVdU5jWWU4MFl2VW5mbE8uYlI0LzNWck03U1I2MGZNejZoRmk2';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://awesome.scot/oauth2/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'code' => $code,
            'client_id' => $clientID,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ));

        $data = curl_exec($ch);
        $data = json_decode($data, true);

        return new JsonResponse($data);
    }
}