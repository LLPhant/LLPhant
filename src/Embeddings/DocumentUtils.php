<?php

namespace LLPhant\Embeddings;

class DocumentUtils
{
    public static function getUniqueId(Document $document): string
    {
        return $document->sourceType.':'.$document->sourceName.':'.$document->chunkNumber;
    }

    public static function formatUUIDFromUniqueId(string $data): string
    {
        $hash = hash('sha256', $data);

        $part1 = substr($hash, 0, 8);
        $part2 = substr($hash, 8, 4);
        $part3 = (hexdec(substr($hash, 12, 4)) & 0x0FFF) | 0x5000;
        $part4 = (hexdec(substr($hash, 16, 4)) & 0x3FFF) | 0x8000;
        $part5 = substr($hash, 20, 12);

        return sprintf('%08s-%04s-%04x-%04x-%12s', $part1, $part2, $part3, $part4, $part5);
    }

    /**
     * @param  array{
     *     content: string,
     *     formattedContent: string|null,
     *     sourceType: string,
     *     sourceName: string,
     *     hash: string,
     *     embedding: float[]|null,
     *     chunkNumber: int,
     *     metadata: array<string, mixed>
     * }[] $documentDataArray
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
     * @param  array{
     *     content: string,
     *     formattedContent: string|null,
     *     sourceType: string,
     *     sourceName: string,
     *     hash: string,
     *     embedding: float[]|null,
     *     chunkNumber: int,
     *     metadata: array<string, mixed>
     * } $documentData
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
     * Create a list of documents from raw content strings.
     *
     * @return Document[]
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
