<?php

namespace ParpV1\MainBundle\Api\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class JsonApiResponse extends JsonResponse
{
    public function __construct($data = 'OK', $status = 200, $headers = array())
    {
        $data = $this->parseResponseData($data, $status);
        parent::__construct($data, $status, $headers);
    }

    /**
     * Przygotowuje dane do przekazania w odpowiedzi jako JSON.
     *
     * @param mixed $data
     * @param int $status
     *
     * @return array
     */
    public function parseResponseData($data, $status)
    {
        $message = trim((string) $data);
        $context = array();

        if (is_array($data)) {
            if (array_key_exists('message')) {
                $message = $data['message'];
            }

            if (array_key_exists('context')) {
                $context = $data['context'];
            }
        }

        return array(
            'message' => $message,
            'context' => $context,
            'code' => $status,
        );
    }
}