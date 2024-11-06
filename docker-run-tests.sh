#!/bin/bash

echo "Running tests in Docker..."

# Step 1: Clear cache in the test environment
docker compose exec web php bin/console cache:clear --env=test

# Step 2: Drop, create, and migrate the test database
echo "Setting up the test database..."
docker compose exec web php bin/console doctrine:database:drop --env=test --force --if-exists
docker compose exec web php bin/console doctrine:database:create --env=test
docker compose exec web php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Step 3: Run PHPUnit tests
echo "Running PHPUnit tests..."
docker compose exec web php bin/phpunit
