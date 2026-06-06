# =============================================================================
# Stage 1 — Build the React + Vite frontend
# Build context must be the project root (.) so both provest-front/ and
# nginx/ are reachable.
#
# Uses node:20 (Debian/glibc) — NOT node:20-alpine — so that the
# node_modules pre-installed on the host (also glibc) are binary-compatible.
# Running npm ci inside Docker is skipped entirely; node_modules is COPY'd
# from the local machine, which already has it installed.
# =============================================================================
FROM node:20 AS frontend-builder

WORKDIR /build

# ── Source files (no node_modules yet) ───────────────────────────────────────
COPY provest-front/package.json provest-front/package-lock.json ./
COPY provest-front/index.html \
     provest-front/vite.config.ts \
     provest-front/postcss.config.js \
     provest-front/tailwind.config.js \
     provest-front/tsconfig.json \
     provest-front/tsconfig.app.json \
     provest-front/tsconfig.node.json \
     provest-front/.env.production ./
COPY provest-front/src ./src

# ── node_modules from host — no npm install inside Docker, no network needed ─
COPY provest-front/node_modules ./node_modules

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
