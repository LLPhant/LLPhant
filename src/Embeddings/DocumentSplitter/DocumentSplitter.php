<?php

namespace LLPhant\Embeddings\DocumentSplitter;

use LLPhant\Embeddings\Document;

final class DocumentSplitter
{
    /**
     * @return Document[]
     */
    public static function splitDocument(Document $document, int $maxLength = 1000, string $separator = ' ', int $wordOverlap = 0, bool $keepSeparator = false): array
    {
        $text = $document->content;
        if (empty($text)) {
            return [];
        }
        if ($maxLength <= 0) {
            return [];
        }

        if ($separator === '') {
            return [];
        }

        if (strlen($text) <= $maxLength) {
            return [$document];
        }

        $words = explode($separator, $text);
        if ($wordOverlap > 0) {
            $chunks = self::createChunksWithOverlap($words, $maxLength, $separator, $wordOverlap, $keepSeparator);
        } else {
            // This method is not really necessary anymore.
            // The new `createChunksWithOverlap` method handles this too.
            // But to prevent possible bugs when introducing the new method,
            // We will handle this case with the old method for now.
            $chunks = self::createChunksWithoutOverlap($words, $maxLength, $separator, $keepSeparator);
        }

        $splittedDocuments = [];
        $chunkNumber = 0;
        foreach ($chunks as $chunk) {
            $className = $document::class;
            $newDocument = new $className();
            $newDocument->content = $chunk;
            $newDocument->hash = hash('sha256', $chunk);
            $newDocument->sourceType = $document->sourceType;
            $newDocument->sourceName = $document->sourceName;
            $newDocument->chunkNumber = $chunkNumber;
            $chunkNumber++;
            $splittedDocuments[] = $newDocument;
        }

        return $splittedDocuments;
    }

    /**
     * @param  Document[]  $documents
     * @return Document[]
     */
    public static function splitDocuments(array $documents, int $maxLength = 1000, string $separator = '.', int $wordOverlap = 0, bool $keepSeparator = false): array
    {
        $splittedDocuments = [];
        foreach ($documents as $document) {
            $splittedDocuments = array_merge($splittedDocuments, DocumentSplitter::splitDocument($document, $maxLength, $separator, $wordOverlap, $keepSeparator));
        }

        return $splittedDocuments;
    }

    /**
     * @param  array<string>  $words
     * @return array<string>
     */
    private static function createChunksWithoutOverlap(array $words, int $maxLength, string $separator, bool $keepSeparator = false): array
    {
        $chunks = [];
        $currentChunk = '';
        foreach ($words as $word) {
            if (strlen($currentChunk.$separator.$word) <= $maxLength || empty($currentChunk)) {
                if (empty($currentChunk)) {
                    $currentChunk = $word;
                } else {
                    $currentChunk .= $separator.$word;
                }
            } else {
                $chunks[] = $keepSeparator ? $currentChunk.$separator : trim($currentChunk);
                $currentChunk = $word;
            }
        }

        if (! empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * @param  array<string>  $words
     * @return array<string>
     */
    private static function createChunksWithOverlap(array $words, int $maxLength, string $separator, int $wordOverlap, bool $keepSeparator = false): array
    {
        $chunks = [];
        $currentChunk = [];
        $currentChunkLength = 0;
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            if ($currentChunkLength + strlen($separator.$word) <= $maxLength || $currentChunk === []) {
                $currentChunk[] = $word;
                $currentChunkLength = self::calcChunkLength($currentChunk, $separator);
            } else {
                // Add the chunk with overlap
                $chunkText = implode($separator, $currentChunk);
                $chunks[] = $keepSeparator ? $chunkText.$separator : $chunkText;

                // Calculate overlap words
                $calculatedOverlap = min($wordOverlap, count($currentChunk) - 1);
                $overlapWords = $calculatedOverlap > 0 ? array_slice($currentChunk, -$calculatedOverlap) : [];

                // Start a new chunk with overlap words
                $currentChunk = [...$overlapWords, $word];
                $currentChunk[0] = trim($currentChunk[0]);
                $currentChunkLength = self::calcChunkLength($currentChunk, $separator);
            }
        }

        if ($currentChunk !== []) {
            $chunks[] = implode($separator, $currentChunk);
        }

        return $chunks;
    }

    /**
     * @param  array<string>  $currentChunk
     */
    private static function calcChunkLength(array $currentChunk, string $separator): int
    {
        return array_sum(array_map('strlen', $currentChunk)) + count($currentChunk) * strlen($separator) - 1;
    }
}
