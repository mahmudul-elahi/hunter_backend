<?php

namespace App\Exceptions;

use Exception;

class SocialAuthException extends Exception
{
    public function __construct(string $message, public int $status = 400)
    {
        parent::__construct($message);
    }

    public static function invalidToken(): self
    {
        return new self('Invalid or expired token.', 401);
    }

    public static function deactivated(): self
    {
        return new self('Account is deactivated.', 403);
    }
}
