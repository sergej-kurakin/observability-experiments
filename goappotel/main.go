package main

import (
	"context"
	"log"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"

	"go.opentelemetry.io/contrib/instrumentation/github.com/gin-gonic/gin/otelgin"
	"go.opentelemetry.io/otel"
	"go.opentelemetry.io/otel/propagation"
	"go.opentelemetry.io/otel/sdk/resource"
	sdktrace "go.opentelemetry.io/otel/sdk/trace"
	semconv "go.opentelemetry.io/otel/semconv/v1.26.0"
	"go.opentelemetry.io/otel/trace"

	"go.opentelemetry.io/otel/exporters/otlp/otlptrace/otlptracehttp"
)

var tracer trace.Tracer

func newExporter(ctx context.Context) (sdktrace.SpanExporter, error) {
	return otlptracehttp.New(ctx)
}

func newTraceProvider(exp sdktrace.SpanExporter) *sdktrace.TracerProvider {
	// Ensure default SDK resources and the required service name are set.
	r, err := resource.Merge(
		resource.Default(),
		resource.NewWithAttributes(
			semconv.SchemaURL,
			// semconv.ServiceName("ExampleService"),
		),
	)

	if err != nil {
		panic(err)
	}

	return sdktrace.NewTracerProvider(
		sdktrace.WithBatcher(exp),
		sdktrace.WithResource(r),
	)
}

func main() {
	ctx := context.Background()

	exp, err := newExporter(ctx)

	if err != nil {
		log.Fatalf("failed to initialize exporter: %v", err)
	}

	tp := newTraceProvider(exp)

	// Handle shutdown properly so nothing leaks.
	defer func() { _ = tp.Shutdown(ctx) }()

	otel.SetTracerProvider(tp)
	tc := propagation.TraceContext{}
	otel.SetTextMapPropagator(tc)

	tracer = tp.Tracer("goappotel")

	r := gin.Default()
	r.Use(otelgin.Middleware("goappotel"))
	r.GET("/ping", func(c *gin.Context) {
		ctx := c.Request.Context()

		doSomeSleep(ctx)
		anotherSleep(ctx)
		doSomeSleep(ctx)

		headers := c.Request.Header

		span := trace.SpanFromContext(ctx)
		traceId := span.SpanContext().TraceID().String()
		spanId := span.SpanContext().SpanID().String()

		c.JSON(http.StatusOK, gin.H{
			"message": "pong",
			"headers": headers,
			"traceId": traceId,
			"spanId":  spanId,
		})
	})
	r.Run(":3020")
}

func doSomeSleep(ctx context.Context) {
	_, span := tracer.Start(ctx, "doSomeSleep")
	defer span.End()

	time.Sleep(10 * time.Millisecond)
}

func anotherSleep(ctx context.Context) {
	ctx, span := tracer.Start(ctx, "anotherSleep")
	defer span.End()

	time.Sleep(5 * time.Millisecond)

	deepSleep(ctx)
}

func deepSleep(ctx context.Context) {
	_, span := tracer.Start(ctx, "deepSleep")
	defer span.End()

	time.Sleep(20 * time.Millisecond)
}
