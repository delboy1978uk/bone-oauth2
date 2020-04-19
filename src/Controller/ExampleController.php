<?php /** @noinspection CurlSslServerSpoofingInspection */

namespace Bone\OAuth2\Controller;

use Bone\OAuth2\Entity\OAuthUser;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class ExampleController
{
    /** @var string  */
    private $clientID = '05c99d2eb8fc4a8019d06a21097f3c46';

    /** @var string  */
    private $redirectUri = 'https://awesome.scot/oauth2/callback';

    /** @var string  */
    private $clientSecret = 'JDJ5JDEwJGVkdlMyNW9xOFlTeG1YMGJVdU5jWWU4MFl2VW5mbE8uYlI0LzNWck03U1I2MGZNejZoRmk2';

    /**
     *  This is an example callback your web site or phone app calling this server would use
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

        // $state = $params['state'];
        // at this point, you would check the state sent in the request is the same as the session stored value
        // if not, throw a 400

        // Now, make a call to the access token endpoint, sending the code sent by the server
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://awesome.scot/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // set this true on your production server!
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'code' => $code,
            'client_id' => $this->clientID,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ));

        $data = curl_exec($ch);
        $data = json_decode($data, true);

        return new JsonResponse($data);
    }

    /**
     * Check basic connectivity. Returns a timestamp.
     * @OA\Get(
     *     path="/ping",
     *     tags={"status"},
     *     @OA\Response(response="200", description="Sends a response with the time")
     * )
     * @param $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Exception
     */
    public function pingAction(ServerRequestInterface $request, array $args) : ResponseInterface
    {
        $now = new DateTime();
        $data = [
            'pong' => $now->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($data);
    }
}