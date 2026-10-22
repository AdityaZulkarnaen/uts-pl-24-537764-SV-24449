# syntax=docker/dockerfile:1

# Stage 1: build the Vue app with Node.js
FROM node:22.23.4-alpine AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Relative by default: Nginx below proxies /api to the backend service.
ARG VITE_API_BASE_URL=/api
ENV VITE_API_BASE_URL=${VITE_API_BASE_URL}

RUN npm run build

# Stage 2: serve only the static build with Nginx (no node_modules)
FROM nginx:1.30.6-alpine

# Backend address inside the Docker Compose network (service name, not an IP).
ENV API_UPSTREAM=http://backend:8000

# The nginx image renders templates with envsubst into conf.d on start.
COPY nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80
