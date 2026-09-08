<?php

namespace App\Exceptions;

use Exception;

class TenantNotFoundException extends Exception
{
    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'Tenant not found or access denied',
            'code' => 'TENANT_NOT_FOUND',
        ], 403);
    }
}
