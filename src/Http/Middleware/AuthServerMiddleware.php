<?php

declare(strict_types=1);

namespace Bone\OAuth2\Http\Middleware;

use Bone\View\ViewEngineInterface;
use Del\Service\UserService;
use Del\SessionManager;
use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class AuthServerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserService $userService,
        private ViewEngineInterface $view,
        private SessionManager $session
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader('X_BONE_USER_ACTIVATE')) {
            return $handler->handle($request);
        }

        $user = $request->getAttribute('user');
        $continueAsUser = $request->getQueryParams()['continue'] ?? false;

        if ($continueAsUser === false) {
            $request = $request->withAttribute('user', null);
            $this->session->set('authRequest', \serialize($request));
            $body = $this->view->render('boneoauth2::continue', [
                'user' => $user,
            ]);

            return new HtmlResponse($body);
        }

        $request = \unserialize($this->session->get('authRequest'));
        $this->session->unset('authRequest');

        return $handler->handle($request);
    }
}
