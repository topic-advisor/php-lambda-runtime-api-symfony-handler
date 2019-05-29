<?php

namespace TopicAdvisor\Lambda\Symfony\Handler;

use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use TopicAdvisor\Lambda\RuntimeApi\Http\HttpRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\Http\HttpResponse;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationResponseInterface;

class RequestHandler extends AbstractHandler
{
    /** @var HttpFoundationFactory */
    private $httpFoundationFactory;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        if ($trustedProxies = getenv('TRUSTED_PROXIES') ?? false) {
            Request::setTrustedProxies(
                explode(',', $trustedProxies),
                Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
            );
        }
        if ($trustedHosts = getenv('TRUSTED_HOSTS') ?? false) {
            Request::setTrustedHosts(explode(',', $trustedHosts));
        }

        $this->httpFoundationFactory = new HttpFoundationFactory();
    }

    /**
     * @param InvocationRequestInterface $request
     * @return bool
     */
    public function canHandle(InvocationRequestInterface $request): bool
    {
        return $request instanceof HttpRequestInterface;
    }

    /**
     * @param InvocationRequestInterface $lambdaRequest
     * @return InvocationResponseInterface
     * @throws \Exception
     */
    public function handle(InvocationRequestInterface $lambdaRequest): InvocationResponseInterface
    {
        $symfonyResponse = $this->kernel->handle($this->httpFoundationFactory->createRequest($lambdaRequest));
        return $this->toLambdaResponse($lambdaRequest, $symfonyResponse);
    }

    /**
     * @param HttpRequestInterface $lambdaRequest
     * @param Response $symfonyResponse
     * @return HttpResponse
     */
    private function toLambdaResponse(HttpRequestInterface $lambdaRequest, Response $symfonyResponse): HttpResponse
    {
        $lambdaResponse = new HttpResponse($lambdaRequest->getInvocationId());

        $headers = $symfonyResponse->headers->all();
        $lambdaResponse->setStatusCode($symfonyResponse->getStatusCode());
        $lambdaResponse->setHeaders($headers);
        $lambdaResponse->setBody($symfonyResponse->getContent());
        $lambdaResponse->setIsBase64Encoded(false);

        return $lambdaResponse;
    }
}