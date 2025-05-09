<?php 
namespace Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\EmptyResponse;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Gérer les requêtes OPTIONS (pré-vol) pour CORS
        if ($request->getMethod() === 'OPTIONS') {
            return new EmptyResponse(200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        // Traiter la requête normale et ajouter les en-têtes CORS à la réponse
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}
