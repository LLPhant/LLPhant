# Agents

This project is LLPhant, which is a PHP library for interacting with LLMs and vector databases.

## Documentation

Besides the [README](README.md) you can find the documentation in the [docs](docs) folder.
This documentation is published on [https://llphant.readthedocs.org](https://llphant.readthedocs.org).
To run the documentation locally, follow the instructions in [Documentation.md](Documentation.md).

## Development environment

The development environment is based on [docker-compose](docker/docker-compose.yml). Assume that no PHP interpreter is
installed on the host machine. Look at the [docker README](docker/README.md) for more information. More info on
development
environment can be found in the [CONTRIBUTING](CONTRIBUTING.md)  file.

### CI pipeline

The CI pipeline is based on GitHub Actions defined in [.github/workflows](.github/workflows).
File [formats](.github/workflows/formats.yml) defines steps for checking code formatting, while
file [tests](.github/workflows/tests.yml) defines steps for running tests.

### Sources

Sources are in the [src](src) folder. There are some examples in the [examples](examples) folder, but you will ignore
them unless explicitly stated.

### Tests

Tests are written with Pest. There are two types of tests: unit tests, and integration tests. CI pipelines run only unit
tests, while integration tests are used for local development.

After having changed your current directory to [docker](docker), you can run tests from the host machine with:

```shell
docker compose exec php vendor/bin/pest tests/subfolder/TestName.php
```

For example: 

```shell
docker compose exec php vendor/bin/pest tests/Integration/Embeddings/VectorStores/OpenSearch/OpenSearchVectorStoreTest.php
```

#### Unit tests

Unit tests are in the [tests](tests/Unit) folder. To run unit tests use `composer test:unit` inside the `php`
container.

#### Integration tests

Integration tests are in the [integration](tests/Integration) folder. To run integration tests you have to set up the
environment variables in the [docker/.env](docker/.env) file.
