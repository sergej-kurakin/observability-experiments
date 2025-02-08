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

            $rootScope->detach();
            $span->end();
        }

        return new Response('Hello World');
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
}
