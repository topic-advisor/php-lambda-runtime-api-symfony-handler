<?php

namespace TopicAdvisor\Lambda\Symfony\Handler;

use Symfony\Component\EventDispatcher\GenericEvent;
use TopicAdvisor\Lambda\RuntimeApi\Event\SQSEvent;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationResponse;
use TopicAdvisor\Lambda\RuntimeApi\InvocationResponseInterface;

class SQSHandler extends AbstractHandler
{
    const EVENT_NAME = 'sqs_event.received';

    /**
     * @param InvocationRequestInterface $request
     * @return bool
     */
    public function canHandle(InvocationRequestInterface $request): bool
    {
        return $request instanceof SQSEvent;
    }

    /**
     * @param InvocationRequestInterface|SQSEvent $request
     * @return InvocationResponseInterface
     * @throws \Exception
     */
    public function handle(InvocationRequestInterface $request): InvocationResponseInterface
    {
        $this->get('event_dispatcher')->dispatch(self::EVENT_NAME, new GenericEvent($request));
        return new InvocationResponse($request->getInvocationId(), ['success' => true]);
    }
}