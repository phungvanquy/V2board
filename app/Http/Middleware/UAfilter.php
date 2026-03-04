<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UAfilter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (strpos($request->header('User-Agent'), 'MicroMessenger') !== false || strpos($request->header('User-Agent'), 'QQ/') !== false) {
            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsupported Browser</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
        p { color: #666; }
    </style>
</head>
<body>
    <h1>Unsupported Browser</h1>
    <p>Sorry, our page cannot be accessed properly in QQ or WeChat browsers.</p>
    <p>Please tap the top-right corner and choose to open in a browser.</p>
</body>
</html>
HTML;
            return response($html, 200)->header('Content-Type', 'text/html');
        }

        if (strpos($request->header('User-Agent'), 'python-requests')) {
            return response('', 200);
        }

        return $next($request);
    }
}
