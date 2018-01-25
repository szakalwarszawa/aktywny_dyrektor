<?php

namespace ParpV1\MainBundle\Api\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class Json404NotFoundResponse
{
    public function __construct($message = "Resource not found.")
    {
        $response = new JsonResponse(array(
            'komunikat' => $message,
        ));
        $response->setStatusCode(404);

        return $response;
    }
}
