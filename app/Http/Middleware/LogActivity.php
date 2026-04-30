<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Hanya log request yang mengubah data (POST, PUT, DELETE, PATCH)
        $methods = ['POST', 'PUT', 'DELETE', 'PATCH'];
        if (in_array($request->method(), $methods)) {
            $user = $request->user();
            
            // Tentukan deskripsi otomatis berdasarkan URL jika tidak ada manual log
            $description = "Request {$request->method()} ke {$request->path()}";
            
            // Jangan double log jika sudah ada manual log di request yang sama?
            // Biasanya kita biarkan saja agar info teknis tetap tercatat
            \App\Models\ActivityLog::create([
                'user_id'      => $user?->id,
                'action'       => strtolower($request->method()),
                'description'  => $description,
                'method'       => $request->method(),
                'url'          => $request->fullUrl(),
                'status_code'  => $response->getStatusCode(),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'company_code' => 'UNIV',
            ]);
        }

        return $response;
    }
}
