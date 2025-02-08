<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;

class DemoController extends AbstractController
{
    private TracerInterface $tracer;

    // public function __construct(private TracerInterface $tracer) {}


    #[Route('/demo', name: 'demo')]
    public function index(): Response
    {
        $this->tracer = Globals::tracerProvider()->getTracer('demo');

        {
            $span = $this->tracer
                ->spanBuilder('manual-span')
                ->startSpan();
            $rootScope = $span->activate();

            $this->runRequest();
            usleep(1000);
            $this->runCache();

            $curlResult = $this->callOtherServiceWithCurl();

            $guzzleResult = $this->callOtherServiceWithGuzzle();

            $httpClient = $this->callOtherServiceWithHttpClient();

            $rootScope->detach();
            $span->end();
        }

        return new Response(
            'Hello World'."\n\n".$curlResult."\n\n".$guzzleResult."\n\n".$httpClient,
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
    }

    private function runRequest(): void
    {
        $span = $this->tracer->spanBuilder('request-100')->startSpan();
        $scope = $span->activate();
        {
            $this->runDatabase();
            usleep(50);
            $this->runDatabase();
        }        

        $scope->detach();
        $span->end();
    }

    private function runDatabase(): void
    {
        $span = $this->tracer->spanBuilder('database-100')->startSpan();
        // $scope = $span->activate();
        {
            usleep(100);
        }
        // $scope->detach();
        $span->end();
    }

    private function runCache(): void
    {
        $span = $this->tracer->spanBuilder('cache-100')->startSpan();
        $scope = $span->activate();
        {
            usleep(101);
        }
        $scope->detach();
        $span->end();
    }

    private function callOtherServiceWithCurl(): string
    {
        $span = $this->tracer->spanBuilder('my-curl')->startSpan();
        $scope = $span->activate();
        
        $handle = curl_init('http://goappotel-collector:3020/ping');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPGET, true);
        $result = curl_exec($handle);

        if (curl_errno($handle)) {
            throw new \RuntimeException(curl_error($handle));
        }

        curl_close($handle);

        $scope->detach();
        $span->end();

        return $result;
    }

    private function callOtherServiceWithGuzzle(): string
    {
        $span = $this->tracer->spanBuilder('my-guzzle')->startSpan();
        $scope = $span->activate();

        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'http://goappotel:3020/ping');
        $result = $response->getBody()->getContents();

        $scope->detach();
        $span->end();

        return $result;
    }

    private function callOtherServiceWithHttpClient(): string
    {
        $span = $this->tracer->spanBuilder('my-http-client')->startSpan();
        $scope = $span->activate();

        $client = \Symfony\Component\HttpClient\HttpClient::create();
        $response = $client->request('GET', 'http://goappotel-collector:3020/ping');
        $result = $response->getContent();

        $scope->detach();
        $span->end();

        return $result;
    }
}
