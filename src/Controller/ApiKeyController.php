<?php

declare(strict_types=1);

namespace Bone\OAuth2\Controller;

use Bone\Exception;
use Bone\Controller\Controller;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Exception\OAuthException;
use Bone\OAuth2\Form\ApiKeyForm;
use Bone\OAuth2\Service\ClientService;
use Del\Entity\User;
use Del\Form\Form;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class ApiKeyController extends Controller
{
    public function __construct(
        private ClientService $clientService
    ) {
    }

    public function myApiKeysAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $user = $request->getAttribute('user');
        $clients = $this->clientService->getClientRepository()->findBy(['user' => $user->getId()]);
        $body = $this->getView()->render('boneoauth2::my-api-keys', ['clients' => $clients]);

        return new HtmlResponse($body);
    }

    public function deleteConfirmAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $client = $this->clientService->getClientRepository()->find($id);
        $clientId = $client->getIdentifier();
        $body = $this->getView()->render('boneoauth2::delete-api-key-confirm', ['clientId' => $clientId]);

        return new HtmlResponse($body);
    }

    public function deleteAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $clientId = $request->getAttribute('id');
        $client = $this->clientService->getClientRepository()->find($clientId);
        $user = $request->getAttribute('user');
        $clientUser = $client->getUser();

        if ($user->getId() !== $clientUser->getId()) {
            throw new OAuthException('Unauthorised', 403);
        }

        $this->clientService->getClientRepository()->delete($client);
        $clients = $this->clientService->getClientRepository()->findBy(['user' => $user->getId(),]);
        $body = $this->getView()->render('boneoauth2::my-api-keys', [
            'clients' => $clients,
            'message' => ['API key ' . $clientId . ' deleted', 'success']
        ]);

        return new HtmlResponse($body);
    }

    public function addAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $form = new ApiKeyForm('addkey', $this->getTranslator());
        $body = $this->getView()->render('boneoauth2::add-key', ['form' => $form->render()]);

        return new HtmlResponse($body);
    }

    public function addSubmitAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $form = new ApiKeyForm('addkey', $this->getTranslator());
        $post = $request->getParsedBody();
        $form->populate($post);

        if ($form->isValid()) {
            $user = $request->getAttribute('user');
            $data = $form->getValues();
            $client = $this->clientService->createFromArray($data, $user);
            $this->clientService->getClientRepository()->create($client);
            $body = $this->getView()->render('boneoauth2::add-key-success', [
                'clientId' => $client->getIdentifier(),
                'clientSecret' => $client->getSecret(),
            ]);
        } else {
            $body = $this->getView()->render('boneoauth2::add-key', ['form' => $form]);
        }

        return new HtmlResponse($body);
    }
}
