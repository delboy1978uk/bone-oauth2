<?php

declare(strict_types=1);

namespace Bone\OAuth2\Http\Middleware;

use Bone\OAuth2\Exception\OAuthException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ScopeCheck implements MiddlewareInterface
{
    /**
     * @param array<string> $scopes
     */
    public function __construct(
        private array $scopes
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientScopes = $request->getAttribute('oauth_scopes');

        if (array_diff($this->scopes, $clientScopes)) {
            throw new OAuthException('Client does not have authorisation scope for this resource.');
        }

        return $handler->handle($request);
    }
}
