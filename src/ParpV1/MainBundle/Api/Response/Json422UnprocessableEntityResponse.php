<?php

namespace ParpV1\MainBundle\Api\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class Json422UnprocessableEntityResponse
{
    public function __construct($message = "Resource is unprocessable.")
    {
        $response = new JsonResponse(array(
            'komunikat' => $message,
        ));
        $response->setStatusCode(422);

        return $response;
    }
}
