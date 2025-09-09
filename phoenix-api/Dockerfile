FROM elixir:1.19-alpine

# Install build dependencies
RUN apk add --no-cache \
    build-base \
    git \
    nodejs \
    npm \
    postgresql-client

# Set working directory
WORKDIR /app

# Install hex and rebar
RUN mix local.hex --force && \
    mix local.rebar --force

# Copy mix files
COPY mix.exs mix.lock ./

# Install dependencies
RUN mix deps.get

# Copy source code
COPY . .

# Compile the project
RUN mix compile

# Install Node.js dependencies for assets
RUN cd assets && npm install

# Compile assets
RUN mix assets.deploy

# Expose port
EXPOSE 4000

# Start the Phoenix server
CMD ["mix", "phx.server"]