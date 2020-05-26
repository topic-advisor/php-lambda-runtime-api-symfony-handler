<?php

namespace TopicAdvisor\Lambda\Symfony\Handler;

use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use TopicAdvisor\Lambda\RuntimeApi\Http\HttpRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\Http\HttpResponse;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationResponseInterface;
use TopicAdvisor\Lambda\RuntimeApi\Util\LambdaDumper;

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
     * @param InvocationRequestInterface|HttpRequestInterface $lambdaRequest
     * @return InvocationResponseInterface
     * @throws \Exception
     */
    public function handle(InvocationRequestInterface $lambdaRequest): InvocationResponseInterface
    {
        $serverParams = $lambdaRequest->getServerParams();
        if ($trustedProxies = $serverParams['TRUSTED_PROXIES'] ?? false) {
            Request::setTrustedProxies(
                explode(',', $trustedProxies),
                Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
            );
        }
        if ($trustedHosts = $serverParams['TRUSTED_HOSTS'] ?? false) {
            Request::setTrustedHosts(explode(',', $trustedHosts));
        }

        $request = $this->httpFoundationFactory->createRequest($lambdaRequest);
        $request->server->set('LAMBDA_PAYLOAD', $lambdaRequest->getPayload());
        $symfonyResponse = $this->kernel->handle($request);

	    if ($this->kernel instanceof TerminableInterface) {
		    $this->kernel->terminate($request, $symfonyResponse);
	    }
	    
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
        if ($cookies = $symfonyResponse->headers->getCookies()) {
            $headers['Set-Cookie'] = $cookies;
        }

        // Set the session cookie if it was not supplied in the request (ie. session is being started in this response)
        if (session_id() && !isset($lambdaRequest->getHeader('Cookie')[session_name()])) {
            $cookie_options = $this->kernel->getContainer()->getParameter('session.storage.options');
            $headers['Set-Cookie'][] = (string) new Cookie(
                session_name(),
                session_id(),
                $cookie_options['cookie_lifetime'] ?? 0,
                $cookie_options['cookie_path'] ?? '/',
                $cookie_options['cookie_domain'] ?? '',
                ($cookie_options['cookie_secure'] ?? 'auto') === 'auto'
                    ? true : (bool) ($cookie_options['cookie_secure'] ?? 'auto'),
                $cookie_options['cookie_httponly'] ?? true,
                false,
                $cookie_options['cookie_samesite'] ?? null
            );
        }

        $lambdaResponse->setStatusCode($symfonyResponse->getStatusCode());
        $lambdaResponse->setHeaders($headers);
        $lambdaResponse->setBody($symfonyResponse->getContent());
        $lambdaResponse->setIsBase64Encoded(false);

        return $lambdaResponse;
    }
}