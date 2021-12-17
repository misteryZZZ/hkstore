<?php

namespace App\Http\Middleware;

use Closure;

class validFilesHost
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(preg_match('/^(GoogleDrive|OneDrive|AmazonS3|DropBox|YandexDisk|Wasabi)$/i', $request->files_host))
            return $next($request);
        
        abort(404);
    }
}
