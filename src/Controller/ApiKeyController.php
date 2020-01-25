<?php

namespace Bone\OAuth2\Controller;

use Bone\Exception;
use Bone\Mvc\Controller;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Service\ClientService;
use Del\Entity\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class ApiKeyController extends Controller
{
    /** @var ClientService $clientService */
    private $clientService;

    /**
     * ApiKeyController constructor.
     * @param ClientService $clientService
     */
    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     */
    public function myApiKeysAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        /** @var OAuthUser $user */
        $user = $request->getAttribute('user');
        $clients = $this->clientService->getClientRepository()->findBy([
           'user' => $user->getId(),
        ]);

        $body = $this->getView()->render('boneoauth2::my-api-keys', ['clients' => $clients]);

        return new HtmlResponse($body);
    }
}