<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Bone\OAuth2\Http\Middleware\AuthServerMiddleware;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Service\PermissionService;
use Bone\View\ViewEngineInterface;
use Del\Service\UserService;
use Del\SessionManager;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Del\Entity\User;
use Laminas\Diactoros\Response\HtmlResponse;

class AuthServerMiddlewareUncoveredTest extends Unit
{
    private AuthServerMiddleware $middleware;
    private UserService $userService;
    private ViewEngineInterface $view;
    private SessionManager $session;
    private AuthorizationServer $authServer;
    private PermissionService $permissionService;

    protected function _before()
    {
        $this->userService = $this->createMock(UserService::class);
        $this->view = $this->createMock(ViewEngineInterface::class);
        $this->session = SessionManager::getInstance();
        $this->authServer = $this->createMock(AuthorizationServer::class);
        $this->permissionService = $this->createMock(PermissionService::class);

        $this->middleware = new AuthServerMiddleware($this->view, $this->session, $this->authServer, $this->permissionService);
    }

    public function testProcessWithXBoneUserActivateHeader()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('X_BONE_USER_ACTIVATE', '1');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());
            
        $response = $this->middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessWithContinueQueryParam()
    {
        $request = new ServerRequest();
        $request = $request->withQueryParams(['continue' => 'true']);
        
        // Mock session to return a serialized request
        $storedRequest = new ServerRequest();
        $this->session->set('authRequest', serialize($storedRequest));
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());
            
        $response = $this->middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessNonProprietaryClientMissingScopesGet()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $user = new User();
        $request = $request->withAttribute('user', $user);
        
        $authRequest = $this->createMock(AuthorizationRequest::class);
        $client = $this->createMock(Client::class);
        $client->method('isProprietary')->willReturn(false);
        $authRequest->method('getClient')->willReturn($client);
        
        $scope1 = new Scope();
        $scope1->setIdentifier('scope1');
        $scope2 = new Scope();
        $scope2->setIdentifier('scope2');
        
        $authRequest->method('getScopes')->willReturn(['scope1' => $scope1, 'scope2' => $scope2]);
        
        $this->authServer->method('validateAuthorizationRequest')->willReturn($authRequest);
        
        // User has only scope1
        $this->permissionService->method('getScopes')->willReturn(['scope1' => $scope1]);
        
        $this->view->expects($this->once())
            ->method('render')
            ->with('boneoauth2::authorize', $this->anything())
            ->willReturn('<html>authorize</html>');
            
        $handler = $this->createMock(RequestHandlerInterface::class);
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testProcessNonProprietaryClientMissingScopesPost()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $user = new User();
        $request = $request->withAttribute('user', $user);
        
        $authRequest = $this->createMock(AuthorizationRequest::class);
        $client = $this->createMock(Client::class);
        $client->method('isProprietary')->willReturn(false);
        $authRequest->method('getClient')->willReturn($client);
        
        $scope1 = new Scope();
        $scope1->setIdentifier('scope1');
        $scope2 = new Scope();
        $scope2->setIdentifier('scope2');
        
        $authRequest->method('getScopes')->willReturn(['scope1' => $scope1, 'scope2' => $scope2]);
        
        $this->authServer->method('validateAuthorizationRequest')->willReturn($authRequest);
        
        // User has only scope1
        $this->permissionService->method('getScopes')->willReturn(['scope1' => $scope1]);
        
        $this->permissionService->expects($this->once())
            ->method('addScopes');
            
        // After adding scopes, it falls through to continue logic? 
        // Wait, looking at code:
        // if (count($missingScopes) && $method === 'POST') { addScopes } 
        // Then it exits the if block? No, it continues.
        // But wait, the code structure:
        /*
            if (!$client->isProprietary()) {
                ... 
                if (missing && GET) { return HtmlResponse }
                if (GET) { return HtmlResponse }  <-- This will be hit if missing && GET didn't return? 
                                                      No, if missing && GET returned, we are done.
                                                      If NOT missing, we hit this.
                if (missing && POST) { addScopes }
            } else {
                return HtmlResponse
            }
        */
        // If missing && POST, it adds scopes. Then what? It falls out of the if block?
        // And then goes to $request = unserialize(...)?
        // This seems like a bug or I misunderstood the code flow.
        // Let's check the code again.
        
        /*
        if ($continueAsUser === false) {
            ...
            if (!$client->isProprietary()) {
                ...
                if (count($missingScopes) && $method === 'GET') { return ... }
                if ($method === 'GET') { return ... }  <-- This handles "no missing scopes" case for GET
                if (count($missingScopes) && $method === 'POST') {
                    $this->permissionService->addScopes($user, $client, $missingScopes);
                }
                // If POST and missing scopes, we add scopes and then... fall through?
                // If POST and NO missing scopes, we fall through?
            } else {
                return ...
            }
        }
        $request = unserialize(...);
        return $handler->handle($request);
        */
        
        // So if POST, it eventually falls through to unserialize and handle.
        // But wait, we just set 'authRequest' in session with user=null.
        // Then we unserialize it immediately?
        // That seems redundant but okay.
        
        // So for this test, we expect it to call handler->handle
        
        $this->session->set('authRequest', serialize(new ServerRequest()));
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new Response());
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessNonProprietaryClientNoMissingScopesGet()
    {
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $user = new User();
        $request = $request->withAttribute('user', $user);
        
        $authRequest = $this->createMock(AuthorizationRequest::class);
        $client = $this->createMock(Client::class);
        $client->method('isProprietary')->willReturn(false);
        $authRequest->method('getClient')->willReturn($client);
        
        $scope1 = new Scope();
        $scope1->setIdentifier('scope1');
        
        $authRequest->method('getScopes')->willReturn(['scope1' => $scope1]);
        
        $this->authServer->method('validateAuthorizationRequest')->willReturn($authRequest);
        
        // User has scope1
        $this->permissionService->method('getScopes')->willReturn(['scope1' => $scope1]);
        
        $this->view->expects($this->once())
            ->method('render')
            ->with('boneoauth2::continue', $this->anything())
            ->willReturn('<html>continue</html>');
            
        $handler = $this->createMock(RequestHandlerInterface::class);
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
