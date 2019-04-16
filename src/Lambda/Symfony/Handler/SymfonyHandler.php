<?php

namespace TopicAdvisor\Lambda\Symfony\Handler;

use PHPPM\Bootstraps\Symfony;
use Symfony\Component\HttpKernel\KernelInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestHandlerInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationResponseInterface;

abstract class SymfonyHandler implements InvocationRequestHandlerInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var Symfony
     */
    private $symfony;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->symfony = new Symfony();
    }

    /**
     * @param InvocationRequestInterface $request
     * @return void
     */
    public function preHandle(InvocationRequestInterface $request)
    {
        $this->symfony->preHandle($this->kernel);
    }

    /**
     * @param InvocationRequestInterface $request
     * @param InvocationResponseInterface $response
     * @return void
     */
    public function postHandle(InvocationRequestInterface $request, InvocationResponseInterface $response)
    {
        $this->symfony->postHandle($this->kernel);
    }
}
