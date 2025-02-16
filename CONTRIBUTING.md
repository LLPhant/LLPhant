# CONTRIBUTING

Contributions are welcome, and are accepted via pull requests.
Please review these guidelines before submitting any pull requests.


## Setup
Clone your fork, then install the dev dependencies:
```bash
composer install
```

You can use the `devx/docker-compose.yml` file to run a local postgresql database with the pgvector extension available.
```bash
docker-compose up -d
```

## Process

1. Fork the project
1. Create a new branch
1. Code, test, commit and push
1. Open a pull request detailing your changes.

## Guidelines

* Please ensure the coding style running `composer lint`.
* Send a coherent commit history, making sure each individual commit in your pull request is meaningful.
* You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
* Please remember that we follow [SemVer](http://semver.org/).

## Refactor

Refactor your code:
```bash
composer refactor
```

## Lint

Lint your code:
```bash
composer lint
```

## Tests

Run all tests:
```bash
composer test
```

Check code quality:
```bash
composer test:refactor
```

Check types:
```bash
composer test:types
```

Unit tests:
```bash
composer test:unit
```

### Integration tests

You'll need a API key from OPENAI and export it as a env var.
You also need to have a postgresql database running with the same parameters 
as in the `docker-compose.yaml` file from `devx` folder.

Then run this sql query to create the table for the tests.

This is the script for PostgreSQL:

```postgresql
CREATE EXTENSION vector;

CREATE TABLE IF NOT EXISTS test_place (
                                          id SERIAL PRIMARY KEY,
                                          content text,
                                          type text,
                                          sourcetype text,
                                          sourcename text,
                                          embedding vector
);
CREATE TABLE IF NOT EXISTS test_doc (
                                        id SERIAL PRIMARY KEY,
                                        content text,
                                        type text,
                                        sourcetype text,
                                        sourcename text,
                                        embedding vector,
                                        chunknumber int
);
```

This is the script for MariaDB:

```mariadb
CREATE DATABASE IF NOT EXISTS llphant;
GRANT ALL ON *.* TO 'root'@'%';

CREATE TABLE IF NOT EXISTS test_place (
                                          id SERIAL PRIMARY KEY,
                                          content text,
                                          type text,
                                          sourcetype text,
                                          sourcename text,
                                          embedding vector(3072) not null,
                                          chunknumber int,
                                          VECTOR INDEX (embedding)
);
CREATE TABLE IF NOT EXISTS test_doc (
                                        id SERIAL PRIMARY KEY,
                                        content text,
                                        type text,
                                        sourcetype text,
                                        sourcename text,
                                        embedding vector(1024) not null,
                                        chunknumber int,
                                        VECTOR INDEX (embedding)
);
```

Then run:
```bash
composer test:int
```

You can set host names and keys for the various services involved in integration tests using these environment variables:
```
OPENAI_API_KEY
MISTRAL_API_KEY
ANTHROPIC_API_KEY
ASTRADB_ENDPOINT
ASTRADB_TOKEN
ELASTIC_URL
PGVECTOR_HOST
REDIS_HOST
MILVUS_HOST
QDRANT_HOST
CHROMADB_HOST
OLLAMA_URL
LAKERA_ENDPOINT
LAKERA_API_KEY
TYPESENSE_API_KEY
TYPESENSE_NODE
MARIADB_HOST
```

