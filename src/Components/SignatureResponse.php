<?php

namespace App\Components;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class SignatureResponse
 * @package Components
 */
class SignatureResponse extends Response
{
    public function __construct($content = [], int $status = 200, array $headers = array())
    {
        $content = json_encode($content);
        parent::__construct($content, $status, $headers);

        $privKey = openssl_pkey_get_private(file_get_contents('bridge.private'));
        openssl_sign($content, $signature, $privKey);

        $this->headers->set('x-shopware-signature', base64_encode($signature));
        $this->headers->set('Content-Type', 'application/json');
    }
}