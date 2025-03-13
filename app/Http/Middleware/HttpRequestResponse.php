<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Str;
use Symfony\Component\HttpFoundation\Response;

class HttpRequestResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        \Log::withContext([
            'request-id' => $requestId
        ]);

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($request->input('password', '') != '') {
            $input = $request->all();
            $input['password'] = Str::mask($request->password, '*', 0);
            $request->replace($input);
        }
        \Log::info('Request body : ' . json_encode($request->all()));
        \Log::info('Response code : ' . $response->getStatusCode());
    }
}
