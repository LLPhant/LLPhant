# Docker environment for running integration tests
In this directory you can find a complete environment for running integration tests

## Docker setup
First `cp .env.dist .env`.
Review options and `docker-compose up -d`.

Inside the docker run `composer install --ignore-platform-req=ext-gd`
