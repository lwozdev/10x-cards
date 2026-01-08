FROM node:20-alpine

# Install dependencies for Playwright (Chromium)
RUN apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    font-noto-emoji

# Set Playwright to use system-installed Chromium
ENV PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 \
    PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Create app directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
# Use npm install if package-lock.json doesn't exist, npm ci if it does
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Keep container running
CMD ["tail", "-f", "/dev/null"]
