<?php

namespace ParpV1\MainBundle\Api\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class Json403ForbiddenResponse
{
    public function __construct($message = "Forbidden resource.")
    {
        $response = new JsonResponse(array(
            'komunikat' => $message,
        ));
        $response->setStatusCode(403);

        return $response;
    }
}
