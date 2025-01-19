package main

import (
	"context"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
	"go.elastic.co/apm/module/apmgin/v2"
	"go.elastic.co/apm/v2"
)

func main() {
	r := gin.Default()
	r.Use(apmgin.Middleware(r))
	r.GET("/ping", func(c *gin.Context) {
		ctx := c.Request.Context()

		doSomeSleep(ctx)
		anotherSleep(ctx)
		doSomeSleep(ctx)

		c.JSON(http.StatusOK, gin.H{
			"message": "pong",
		})
	})
	r.Run(":3010")
}

func doSomeSleep(ctx context.Context) {
	span, _ := apm.StartSpan(ctx, "doSomeSleep", "doSomeSleep")
	defer span.End()

	time.Sleep(10 * time.Millisecond)
}

func anotherSleep(ctx context.Context) {
	span, ctx := apm.StartSpan(ctx, "anotherSleep", "anotherSleep")
	defer span.End()

	time.Sleep(5 * time.Millisecond)

	deepSleep(ctx)
}

func deepSleep(ctx context.Context) {
	span, _ := apm.StartSpan(ctx, "deepSleep", "deepSleep")
	defer span.End()

	time.Sleep(20 * time.Millisecond)
}
