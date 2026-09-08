<?php

namespace App\Exceptions;

use Exception;

class AuthenticationException extends Exception
{
    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'Authentication failed',
            'code' => 'AUTHENTICATION_FAILED',
        ], 401);
    }
}
