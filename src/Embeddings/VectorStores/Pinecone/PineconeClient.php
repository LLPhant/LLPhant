<?php

namespace LLPhant\Embeddings\VectorStores\Pinecone;

use Probots\Pinecone\Client as ProbotsClient;

class PineconeClient
{
    public ProbotsClient $client;

    /**
     * @param  string  $host  The full index host url, including scheme (e.g., https://index-name-project.svc.environment.pinecone.io or http://localhost:5080)
     * @param  string  $apiKey  The Pinecone API Key (optional when using a local Pinecone server)
     */
    public function __construct(
        public string $host,
        public string $apiKey = ''
    ) {
        $this->client = new ProbotsClient($apiKey);
        $this->client->setIndexHost($host);
    }

    /**
     * Creates a new Pinecone index and returns the index host URL.
     *
     * NOTE: For the local Pinecone server (pinecone-local) the control plane and data plane
     * share the same host, so $host is used for index-creation requests.
     * For production Pinecone the control plane lives at https://api.pinecone.io; pass that
     * value as $controlHost when targeting a real Pinecone environment.
     *
     * @param  string  $name  Index name
     * @param  int  $dimension  Vector dimension (must match the embedding generator)
     * @param  string  $metric  Similarity metric ('cosine', 'euclidean', 'dotproduct')
     * @param  string|null  $controlHost  Override for the control-plane base URL (defaults to $host)
     * @return string The index host URL to use for data-plane operations
     */
    public function createIndex(
        string $name,
        int $dimension,
        string $metric = 'cosine',
        ?string $controlHost = null
    ): string {
        // Create a dedicated client for control-plane operations so we don't mutate
        // the state of the data-plane client ($this->client).
        $controlClient = new ProbotsClient($this->apiKey);
        $controlClient->setIndexHost($controlHost ?? $this->host);
        // Calling data() is the only way to switch baseUrl to the desired host in this SDK.
        // For local Pinecone the control URL equals the data URL, so this is correct.
        $controlClient->data();

        // Check whether the index already exists before attempting to create it.
        $listResponse = $controlClient->control()->index()->list();
        $indexes = $listResponse->json()['indexes'] ?? [];
        foreach ($indexes as $index) {
            if (($index['name'] ?? '') === $name) {
                return $index['host'] ?? $this->host;
            }
        }

        $response = $controlClient->control()->index($name)->createServerless(
            dimension: $dimension,
            metric: $metric,
        );

        if (! $response->successful()) {
            throw new \Exception('Failed to create Pinecone index: '.$response->body());
        }

        return $response->json()['host'] ?? $this->host;
    }

    /**
     * Deletes a Pinecone index. Safe to call when the index does not exist (idempotent).
     *
     * @param  string  $name  Index name
     * @param  string|null  $controlHost  Override for the control-plane base URL (defaults to $host)
     */
    public function deleteIndex(string $name, ?string $controlHost = null): void
    {
        $controlClient = new ProbotsClient($this->apiKey);
        $controlClient->setIndexHost($controlHost ?? $this->host);
        $controlClient->data();

        // Only delete if the index actually exists.
        $listResponse = $controlClient->control()->index()->list();
        $indexes = $listResponse->json()['indexes'] ?? [];
        foreach ($indexes as $index) {
            if (($index['name'] ?? '') === $name) {
                $controlClient->control()->index($name)->delete();

                return;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function describeIndexStats(): array
    {
        $response = $this->client->data()->vectors()->stats();

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch index stats: '.$response->body());
        }

        return $response->json();
    }

    /**
     * @param  array<int, array<string, mixed>>  $vectors
     */
    public function upsert(array $vectors, string $namespace = ''): bool
    {
        $response = $this->client->data()->vectors()->upsert(
            vectors: $vectors,
            namespace: $namespace
        );

        return $response->successful();
    }

    /**
     * @param  float[]  $vector
     * @param  array<string, mixed>|null  $filter
     * @return array<mixed>
     */
    public function query(
        array $vector,
        int $topK,
        ?array $filter = null,
        string $namespace = '',
        bool $includeMetadata = true,
        bool $includeValues = false
    ): array {
        $response = $this->client->data()->vectors()->query(
            vector: $vector,
            namespace: $namespace,
            filter: $filter ?? [],
            topK: $topK,
            includeMetadata: $includeMetadata,
            includeValues: $includeValues
        );

        if (! $response->successful()) {
            throw new \Exception('Pinecone Query Failed: '.$response->body());
        }

        return $response->json()['matches'] ?? [];
    }
}
