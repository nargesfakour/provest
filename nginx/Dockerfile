# =============================================================================
# Stage 1 — Build the React + Vite frontend
# Build context must be the project root (.) so both provest-front/ and
# nginx/ are reachable.
# =============================================================================
FROM node:20-alpine AS frontend-builder

WORKDIR /build

# Copy lockfiles first — npm install layer is cached until they change.
COPY provest-front/package.json provest-front/package-lock.json ./

RUN npm ci --ignore-scripts

# Copy the full frontend source.
# .env.production is included here and Vite will use it automatically.
COPY provest-front/ .

RUN npm run build

# =============================================================================
# Stage 2 — Production Nginx image
# =============================================================================
FROM nginx:1.27-alpine

LABEL org.opencontainers.image.title="provest-nginx"
LABEL org.opencontainers.image.description="Provest — Nginx reverse proxy + React SPA"

# Remove the default catch-all site.
RUN rm -f /etc/nginx/conf.d/default.conf

# Copy compiled React app into nginx html root.
COPY --from=frontend-builder /build/dist /usr/share/nginx/html

# Copy production nginx site configuration.
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# nginx official image already runs as the nginx user (non-root).
EXPOSE 80 443
