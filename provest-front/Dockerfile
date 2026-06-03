# =============================================================================
# Stage 1 — Build the React + Vite application
# =============================================================================
FROM node:20-alpine AS builder

WORKDIR /app

# Install dependencies first for better layer caching.
# package-lock.json is used for a reproducible install.
COPY package.json package-lock.json ./

RUN npm ci --ignore-scripts

# Copy source and run the production build.
# .env.production is included in the source and will be picked up by Vite.
COPY . .

RUN npm run build

# =============================================================================
# Stage 2 — Minimal artifact image
# Contains only the compiled dist/ output.
# Used by nginx/Dockerfile via COPY --from=provest-frontend /dist.
# =============================================================================
FROM alpine:3.19 AS release

COPY --from=builder /app/dist /dist
