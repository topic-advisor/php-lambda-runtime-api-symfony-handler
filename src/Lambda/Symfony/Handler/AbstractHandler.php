<?php

namespace TopicAdvisor\Lambda\Symfony\Handler;

use Symfony\Component\HttpKernel\KernelInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestHandlerInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationResponseInterface;

abstract class AbstractHandler implements InvocationRequestHandlerInterface
{
    /** @var KernelInterface */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param InvocationRequestInterface $request
     * @return void
     */
    public function preHandle(InvocationRequestInterface $request)
    {

    }

    /**
     * @param InvocationRequestInterface $request
     * @param InvocationResponseInterface $response
     * @return void
     */
    public function postHandle(InvocationRequestInterface $request, InvocationResponseInterface $response)
    {
        if ($this->kernel->getContainer()->has('services_resetter')) {
	        $this->kernel->getContainer()->get('services_resetter')->reset();
        }
    }

    /**
     * @param string $serviceName
     * @return object
     */
    protected function get(string $serviceName)
    {
        return $this->kernel->getContainer()->get($serviceName);
    }
}
