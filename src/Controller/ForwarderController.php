<?php

namespace App\Controller;

use App\Components\Helper;
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
     * @return JsonResponse
     */
    public function proxy(Request $request)
    {
        return new JsonResponse(Helper::proxy($request));
    }
}