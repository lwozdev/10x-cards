FROM node:20-bookworm

# Install dependencies for Playwright browsers
RUN apt-get update && apt-get install -y \
    libnss3 \
    libnspr4 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libdbus-1-3 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libasound2 \
    libpango-1.0-0 \
    libcairo2 \
    && rm -rf /var/lib/apt/lists/*

# Create app directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install npm dependencies
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Install Playwright browsers (only Chromium for E2E tests)
RUN npx playwright install --with-deps chromium

# Keep container running
CMD ["tail", "-f", "/dev/null"]
