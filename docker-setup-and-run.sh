#!/bin/bash

echo "Starting project setup with Docker..."

# Step 1: Build and start Docker containers
echo "Building and starting Docker containers..."
docker compose up -d --build

# Step 2: Install dependencies in the PHP container
echo "Installing dependencies in the Docker PHP container..."
docker compose exec web composer install

# Step 3: Generate JWT keys if they don't exist
echo "Checking for JWT keys..."
docker compose exec web bash -c '
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "Generating JWT keys..."
    mkdir -p config/jwt
    openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256 -pass pass:${JWT_PASSPHRASE:-your_passphrase}
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin pass:${JWT_PASSPHRASE:-your_passphrase}
else
    echo "JWT keys already exist."
fi
'

# Step 4: Set up the database
echo "Setting up the database..."
docker compose exec web php bin/console doctrine:database:create --if-not-exists
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction

# Step 5: Load fixtures if any
echo "Loading fixtures..."
docker compose exec web php bin/console doctrine:fixtures:load --no-interaction

# Step 6: Output the running containers and logs
echo "Docker containers are running. You can view logs with 'docker compose logs'."
docker compose logs -f
