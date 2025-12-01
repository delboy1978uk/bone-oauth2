<?php

declare(strict_types=1);

namespace Bone\OAuth2\Http\Middleware;

use Bone\OAuth2\Service\PermissionService;
use Bone\View\ViewEngineInterface;
use Del\SessionManager;
use Laminas\Diactoros\Response\HtmlResponse;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthServerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ViewEngineInterface $view,
        private SessionManager $session,
        private AuthorizationServer $authServer,
        private PermissionService $permissionService,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader('X_BONE_USER_ACTIVATE')) {
            return $handler->handle($request);
        }

        $user = $request->getAttribute('user');
        $continueAsUser = $request->getQueryParams()['continue'] ?? false;

        if ($continueAsUser === false) {
            $authRequest = $this->authServer->validateAuthorizationRequest($request);
            $client = $authRequest->getClient();
            $request = $request->withAttribute('user', null);
            $this->session->set('authRequest', \serialize($request));

            if (!$client->isProprietary()) {
                $scopes = $authRequest->getScopes();
                $userScopes = $this->permissionService->getScopes($user, $client);
                $missingScopes = array_diff_key($scopes, $userScopes);
                $approvedCount = count($scopes) - count($missingScopes);
                $method = $request->getMethod();

                if (count($missingScopes) && $method === 'GET') {
                    $body = $this->view->render('boneoauth2::authorize', [
                        'scopes' => $scopes,
                        'approvedCount' => $approvedCount,
                        'missingScopes' => $missingScopes,
                        'client' => $client,
                        'user' => $user,
                    ]);

                    return new HtmlResponse($body);
                }

                if ($method === 'GET') {
                    $body = $this->view->render('boneoauth2::continue', [
                        'user' => $user,
                    ]);

                    return new HtmlResponse($body);
                }

                if (count($missingScopes) && $method === 'POST') {
                    $this->permissionService->addScopes($user, $client, $missingScopes);
                }
            } else {
                $body = $this->view->render('boneoauth2::continue', [
                    'user' => $user,
                ]);

                return new HtmlResponse($body);
            }
        }

        $request = \unserialize($this->session->get('authRequest'));
        $this->session->unset('authRequest');

        return $handler->handle($request);
    }
}
