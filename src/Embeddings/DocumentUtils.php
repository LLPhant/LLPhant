<?php

namespace LLPhant\Embeddings;

class DocumentUtils
{
    public static function getUniqueId(Document $document): string
    {
        return $document->sourceType.':'.$document->sourceName.':'.$document->chunkNumber;
    }

    /**
     * Qdrant needs to have uuid format for their ids.
     * As we want deterministic IDs for idempotency we use this barbaric function
     * that has a *very* low probability of collision (50% chance every 2^64 inputs)
     */
    public static function formatUUIDFromUniqueId(string $data): string
    {
        // 1. Generate a SHA-256 hash of the data.
        $hash = hash('sha256', $data);

        // 2. Extract portions of the hash to form the UUID.
        $part1 = substr($hash, 0, 8);
        $part2 = substr($hash, 8, 4);

        // For parts 3 and 4, we're making adjustments to ensure the UUID is a valid version 5 UUID.
        $part3 = (hexdec(substr($hash, 12, 4)) & 0x0FFF) | 0x5000;
        $part4 = (hexdec(substr($hash, 16, 4)) & 0x3FFF) | 0x8000;

        $part5 = substr($hash, 20, 12);

        // 3. Combine the parts to form the UUID.
        $uuid = sprintf('%08s-%04s-%04x-%04x-%12s', $part1, $part2, $part3, $part4, $part5);

        return $uuid;
    }

    /**
     * @param  array{content: string, formattedContent: string, sourceType: string, sourceName: string, hash: string, embedding: float[], chunkNumber: int, metadata?: array<string, mixed>}[]  $documentDataArray
     * @return Document[]
     */
    public static function createDocumentsFromArray(array $documentDataArray): array
    {
        $documents = [];
        foreach ($documentDataArray as $documentData) {
            $documents[] = self::createDocumentFromArray($documentData);
        }

        return $documents;
    }

    /**
     * @param  array{content: string, formattedContent: string, sourceType: string, sourceName: string, hash: string, embedding: float[], chunkNumber: int, metadata?: array<string, mixed>}  $documentData
     */
    public static function createDocumentFromArray(array $documentData): Document
    {
        $document = new Document();
        $document->content = $documentData['content'];
        $document->formattedContent = $documentData['formattedContent'];
        $document->embedding = $documentData['embedding'];
        $document->sourceType = $documentData['sourceType'];
        $document->sourceName = $documentData['sourceName'];
        $document->hash = $documentData['hash'];
        $document->chunkNumber = $documentData['chunkNumber'];
        $document->metadata = $documentData['metadata'] ?? [];

        return $document;
    }

    public static function getFirstWordFromContent(Document $document): string
    {
        return explode(' ', $document->content)[0];
    }

    public static function getUtf8Data(Document $document): string
    {
        return self::toUtf8($document->formattedContent ?? $document->content);
    }

    public static function toUtf8(string $string): string
    {
        return mb_convert_encoding($string, 'UTF-8');
    }

    /**
     * @return array<Document>
     */
    public static function documents(string ...$contents): array
    {
        $result = [];
        foreach ($contents as $content) {
            $newDocument = self::document($content);
            $result[] = $newDocument;
        }

        return $result;
    }

    public static function document(string $content): Document
    {
        $newDocument = new Document();
        $newDocument->content = $content;

        return $newDocument;
    }
}
