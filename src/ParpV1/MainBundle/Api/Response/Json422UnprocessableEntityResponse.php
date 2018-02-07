<?php

namespace ParpV1\MainBundle\Api\Response;

use ParpV1\MainBundle\Api\Response\JsonApiResponse;

class Json422UnprocessableEntityResponse extends JsonApiResponse
{
    public function __construct($data = "Unprocessable Entity", $status = 422, $headers = array())
    {
        parent::__construct($data, $status, $headers);
    }
}
