<?php

namespace App\Components;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class GlobalForwarder
 * @package Components
 */
class GlobalForwarder implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.exception' => 'onKernelException'
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $response = new JsonResponse(Helper::proxy($event->getRequest()));
        $event->allowCustomResponseCode();
        $event->setResponse($response);
    }
}
