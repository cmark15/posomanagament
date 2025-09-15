<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
     /**
     * URIs that should be excluded from CSRF verification.
     * Use paths without a leading slash. Wildcards allowed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'pwa/sync/ticket'
    ];
    
}
