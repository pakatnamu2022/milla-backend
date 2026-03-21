<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DocsBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->getUser();
        $pass = $request->getPassword();

        if ($user !== env('DOCS_USER') || $pass !== env('DOCS_PASSWORD')) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="API Docs"',
            ]);
        }

        return $next($request);
    }
}
