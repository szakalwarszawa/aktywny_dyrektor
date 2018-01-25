<?php

namespace ParpV1\MainBundle\Api\Response;

use ParpV1\MainBundle\Api\Response\JsonApiResponse;

class Json404NotFoundResponse extends JsonApiResponse
{
    public function __construct($data = "Not Found", $status = 404, $headers = array())
    {
        parent::__construct($data, $status, $headers);
    }
}
