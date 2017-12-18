<?php

namespace App\Controller;

use App\Components\Helper;
use App\Components\SignatureResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class ForwarderController
 * @package App\Controller
 */
class ForwarderController extends Controller
{
    /**
     * @param Request $request
     * @return SignatureResponse
     */
    public function proxy(Request $request)
    {
        return new SignatureResponse(Helper::proxy($request));
    }
}