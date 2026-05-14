# AtlasCloud Provider Review

## Summary

This change adds a minimal Atlas Cloud provider integration for LLPhant by
reusing the existing OpenAI-compatible chat stack.

## What Changed

1. Added `LLPhant\AtlasCloudConfig`
   - Default base URL: `https://api.atlascloud.ai/v1`
   - Default model: `owl`
   - Default API key env var: `ATLASCLOUD_API_KEY`

2. Reused the existing `OpenAIChat` integration
   - No new chat client was introduced
   - Atlas Cloud works through the OpenAI-compatible implementation already in LLPhant

3. Added focused unit coverage
   - Default values
   - Environment variable loading
   - Override behavior for URL, model, and model options

4. Updated project docs
   - Added Atlas Cloud usage example in `docs/usage.rst`
   - Added Atlas Cloud to the features matrix in `docs/features.rst`
   - Added Atlas Cloud to the provider list in `README.md`

5. Updated local dev wiring
   - Added `ATLASCLOUD_API_KEY` to `docker/.env.dist`
   - Passed `ATLASCLOUD_API_KEY` into the PHP container in `docker/docker-compose.yml`
   - Documented the env var in `CONTRIBUTING.md`

6. Unblocked local startup
   - Updated `docker/php/Dockerfile` from `xdebug-3.5.0` to `xdebug-3.5.1`
   - This fixes the local PHP image build so the project can start and tests can run

## Local Validation Plan

1. Store the Atlas Cloud key only in local ignored files
2. Bring up the docker-based PHP environment
3. Run the new unit test
4. Run a live smoke test against Atlas Cloud chat completion

## Review Focus

1. Naming: `AtlasCloudConfig`
2. Default model choice: `owl`
3. Scope: keep this PR limited to the OpenAI-compatible LLM path only
