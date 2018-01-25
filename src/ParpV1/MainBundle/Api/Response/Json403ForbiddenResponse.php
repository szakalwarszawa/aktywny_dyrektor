<?php

namespace ParpV1\MainBundle\Api\Response;

use ParpV1\MainBundle\Api\Response\JsonApiResponse;

class Json403ForbiddenResponse extends JsonApiResponse
{
    public function __construct($data = "Forbidden", $status = 403, $headers = array())
    {
        parent::__construct($data, $status, $headers);
    }
}
