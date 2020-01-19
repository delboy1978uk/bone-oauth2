<?php

namespace Bone\OAuth2\Exception;

use Exception;
use Throwable;

class OAuthException extends Exception
{
    public function __construct(string $message = '', int $code = 403, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}