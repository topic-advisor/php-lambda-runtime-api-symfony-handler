<?php

namespace TopicAdvisor\Lambda\Symfony\Handler;

use Symfony\Component\HttpKernel\KernelInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestHandlerInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationResponseInterface;

class SymfonyInvocationRequestHandler implements InvocationRequestHandlerInterface
{
    /** @var KernelInterface */
    private $kernel;

    /** @var  InvocationRequestHandlerInterface[] */
    private $handlers;

    /** @var InvocationRequestHandlerInterface */
    private $currentHandler;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->handlers = [
            new RequestHandler($kernel),
            new SQSHandler($kernel),
        ];
    }

    /**
     * @param InvocationRequestInterface $request
     * @return bool
     */
    public function canHandle(InvocationRequestInterface $request): bool
    {
        return null !== ($this->currentHandler = $this->getHandler($request));
    }

    /**
     * @param InvocationRequestInterface $request
     * @return void
     */
    public function preHandle(InvocationRequestInterface $request)
    {
        $this->currentHandler->preHandle($request);
    }

    /**
     * @param InvocationRequestInterface $request
     * @return InvocationResponseInterface
     * @throws \Exception
     */
    public function handle(InvocationRequestInterface $request): InvocationResponseInterface
    {
        return $this->currentHandler->handle($request);
    }

    /**
     * @param InvocationRequestInterface $request
     * @param InvocationResponseInterface $response
     * @return void
     */
    public function postHandle(InvocationRequestInterface $request, InvocationResponseInterface $response)
    {
        $this->currentHandler->postHandle($request, $response);
    }

    /**
     * @param InvocationRequestInterface $request
     * @return null|InvocationRequestHandlerInterface
     */
    private function getHandler(InvocationRequestInterface $request): ?InvocationRequestHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($request)) {
                return $handler;
            }
        }

        return null;
    }
}