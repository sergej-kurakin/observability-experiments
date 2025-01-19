FROM golang:1.23-alpine3.21 AS build-stage

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download

COPY main.go ./

RUN CGO_ENABLED=0 GOOS=linux go build -o /main

FROM alpine:3.21

COPY --from=build-stage /main /main

EXPOSE 3010

ENTRYPOINT ["/main"]
