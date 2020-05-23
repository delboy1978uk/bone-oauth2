<?php

namespace Bone\OAuth2\Http\Middleware;

use Bone\OAuth2\Exception\OAuthException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ScopeCheck implements MiddlewareInterface
{
    /** @var array $scopes */
    private $scopes;

    /**
     * ScopeCheck constructor.
     * @param array $scopes
     */
    public function __construct(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientScopes = $request->getAttribute('oauth_scopes');

        if (array_diff($this->scopes, $clientScopes)) {
            throw new OAuthException('Client does not have authorisation scope for this resource.');
        }

        return $handler->handle($request);
    }
}