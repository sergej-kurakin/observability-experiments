<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenTelemetry\API\Globals;

use OpenTelemetry\API\Signals;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Grpc\GrpcTransportFactory;
use OpenTelemetry\Contrib\Otlp\OtlpUtil;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\SDK\Common\Export\Stream\StreamTransportFactory;


class DemoController extends AbstractController
{
    private TracerInterface $tracer;


    #[Route('/demo', name: 'demo')]
    public function index(): Response
    {
        $this->setupOtelSDK();

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

    private function setupOtelSDK(): void
    {
        $resource = ResourceInfoFactory::emptyResource()->merge(ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAMESPACE => 'demo',
            ResourceAttributes::SERVICE_NAME => 'test-application',
            ResourceAttributes::SERVICE_VERSION => '0.1',
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => 'development',
        ])));

        $transport = (new GrpcTransportFactory())
            ->create('http://otel-collector:4317' . OtlpUtil::method(Signals::TRACE));
        $exporter = new SpanExporter($transport);

        // $exporter = new SpanExporter(
        //     (new StreamTransportFactory())->create('php://stdout', 'application/json')
        // );

        $tracerProvider =  new TracerProvider(
            new SimpleSpanProcessor($exporter),
            null,
            $resource
        );

        Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setPropagator(TraceContextPropagator::getInstance())
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();
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
