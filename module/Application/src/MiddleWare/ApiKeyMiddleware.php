<?php
namespace Application\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiKeyMiddleware implements MiddlewareInterface
{
    private const VALID_API_KEYS = [
        'your-secret-api-key', // Remplacez par vos clés valides
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $apiKey = $request->getHeaderLine('X-API-KEY');

        if (!in_array($apiKey, self::VALID_API_KEYS, true)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid or missing API Key',
            ], 401); // Unauthorized
        }

        return $handler->handle($request);
    }
}
