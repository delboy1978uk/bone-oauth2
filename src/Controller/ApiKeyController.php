<?php

namespace Bone\OAuth2\Controller;

use Bone\Exception;
use Bone\Mvc\Controller;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Form\ApiKeyForm;
use Bone\OAuth2\Service\ClientService;
use Del\Form\Form;
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
        $clients = $this->clientService->getClientRepository()->findBy(['user' => $user->getId()]);

        $body = $this->getView()->render('boneoauth2::my-api-keys', ['clients' => $clients]);

        return new HtmlResponse($body);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     */
    public function deleteConfirmAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = $request->getAttribute('id');
        /** @var Client $client */
        $client = $this->clientService->getClientRepository()->find($id);
        $clientId = $client->getIdentifier();
        $body = $this->getView()->render('boneoauth2::delete-api-key-confirm', ['clientId' => $clientId]);

        return new HtmlResponse($body);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $clientId = $request->getAttribute('id');
        /** @var Client $client */
        $client = $this->clientService->getClientRepository()->find($clientId);
        /** @var OAuthUser $user */
        $user = $request->getAttribute('user');
        $clientUser = $client->getUser();

        if ($user->getId() !== $clientUser->getId()) {
            throw new Exception('Unauthorised', 403);
        }

        $this->clientService->getClientRepository()->delete($client);
        $clients = $this->clientService->getClientRepository()->findBy(['user' => $user->getId(),]);
        $body = $this->getView()->render('boneoauth2::my-api-keys', [
            'clients' => $clients,
            'message' => ['API key ' . $clientId . ' deleted', 'success']
        ]);

        return new HtmlResponse($body);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     */
    public function addAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $form = new ApiKeyForm('addkey', $this->getTranslator());
        $body = $this->getView()->render('boneoauth2::add-key', ['form' => $form->render()]);

        return new HtmlResponse($body);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Exception
     */
    public function addSubmitAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $form = new ApiKeyForm('addkey', $this->getTranslator());
        $post = $request->getParsedBody();
        $form->populate($post);

        if ($form->isValid()) {
            /** @var OAuthUser $user */
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