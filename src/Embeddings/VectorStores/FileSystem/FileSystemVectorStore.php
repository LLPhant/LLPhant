<?php

namespace LLPhant\Embeddings\VectorStores\FileSystem;

use Exception;
use LLPhant\Embeddings\Distances\Distance;
use LLPhant\Embeddings\Distances\EuclideanDistanceL2;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentStore\DocumentStore;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;

class FileSystemVectorStore extends VectorStoreBase implements DocumentStore
{
    public string $filePath;

    /**
     * Create or open a vector storage in a local .json file
     *
     * @param  ?string  $filepath  Full path to the .json that stores the vector data. Pass "null" to default to a local directory.
     */
    public function __construct(?string $filepath = null, private readonly Distance $distance = new EuclideanDistanceL2())
    {
        $this->filePath = $filepath ?? getcwd().DIRECTORY_SEPARATOR.'documents-vectorStore.json';
    }

    public function addDocument(Document $document): void
    {
        $jsonString = \json_encode($this->documentToArray($document), JSON_THROW_ON_ERROR);
        $line = \base64_encode($jsonString);

        // Open file in append mode
        $file = \fopen($this->filePath, 'a');

        if (! $file) {
            throw new Exception('Unable to open file for writing.');
        }

        // Write JSON string to the end of the file
        \fwrite($file, $line."\n");

        fclose($file);
    }

    public function addDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            $this->addDocument($document);
        }
    }

    /**
     * @param  float[]  $embedding
     * @param  array<string, string|int>  $additionalArguments
     * @return Document[]
     */
    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): array
    {
        $file = fopen($this->filePath, 'r');
        if (! $file) {
            throw new Exception('Unable to open file for reading.');
        }

        $distanceList = new DistanceList($k);

        foreach ($this->yieldJsonObjectsFromFile($file) as $document) {
            if ($document->embedding === null) {
                throw new Exception("Document with the following content has no embedding: {$document->content}");
            }
            $dist = $this->distance->measure($embedding, $document->embedding);
            $distanceList->addDistance($dist, $document);
        }

        \fclose($file);

        return $distanceList->getDocuments();
    }

    /**
     * @param  resource  $file
     * @return \Generator<Document>
     */
    private function yieldJsonObjectsFromFile(mixed $file): \Generator
    {
        while (($line = fgets($file)) !== false) {
            $trimmedLine = trim($line);
            if (! empty($trimmedLine)) {
                $decodedObject = DocumentUtils::createDocumentFromArray(\json_decode(\base64_decode($trimmedLine), true));
                if (\json_last_error() === JSON_ERROR_NONE) {
                    yield $decodedObject;
                } else {
                    throw new \RuntimeException("Warning: Invalid JSON on line: $trimmedLine");
                }
            }
        }
    }

    public function isEmpty(): bool
    {
        $file = fopen($this->filePath, 'r');
        if (! $file) {
            throw new Exception('Unable to open file for reading.');
        }

        $result = ! $this->yieldJsonObjectsFromFile($file)->current() instanceof \LLPhant\Embeddings\Document;

        \fclose($file);

        return $result;
    }

    public function getNumberOfDocuments(): int
    {
        $file = fopen($this->filePath, 'r');
        if (! $file) {
            throw new Exception('Unable to open file for reading.');
        }

        $result = count(iterator_to_array($this->yieldJsonObjectsFromFile($file), false));

        \fclose($file);

        return $result;
    }

    /**
     * @return Document[]
     */
    private function readDocumentsFromFile(): array
    {
        $file = fopen($this->filePath, 'r');
        if (! $file) {
            throw new Exception('Unable to open file for reading.');
        }

        $result = \iterator_to_array($this->yieldJsonObjectsFromFile($file), false);

        \fclose($file);

        return $result;
    }

    public function fetchDocumentsByChunkRange(string $sourceType, string $sourceName, int $leftIndex, int $rightIndex): iterable
    {
        // This is a naive implementation, just to create an example of a DocumentStore
        $result = [];

        $documentsPool = $this->readDocumentsFromFile();

        foreach ($documentsPool as $document) {
            if ($document->sourceType === $sourceType && $document->sourceName === $sourceName && $document->chunkNumber >= $leftIndex && $document->chunkNumber <= $rightIndex) {
                $result[$document->chunkNumber] = $document;
            }
        }

        \ksort($result);

        return $result;
    }

    public function deleteStore(): bool
    {
        if (! is_readable($this->filePath)) {
            return false;
        }

        return \unlink($this->filePath);
    }

    /**
     * @return array{content: string, formattedContent: string|null, embedding: float[]|null, sourceType: string, sourceName: string, chunkNumber: int, hash: string}
     */
    private function documentToArray(Document $document): array
    {
        return [
            'content' => $document->content,
            'formattedContent' => $document->formattedContent,
            'embedding' => $document->embedding,
            'sourceType' => $document->sourceType,
            'sourceName' => $document->sourceName,
            'chunkNumber' => $document->chunkNumber,
            'hash' => $document->hash,
        ];
    }

    public function convertFromOldFileFormat(string $pathOfOldStore): void
    {
        $oldStore = new FileSystemVectorStore($pathOfOldStore);
        $documents = $oldStore->readDocumentsFromOldFileFormat();
        $this->addDocuments($documents);
    }

    /**
     * @return Document[]
     */
    private function readDocumentsFromOldFileFormat(): array
    {
        // Check if file exists and we can open it
        if (! is_readable($this->filePath)) {
            return [];
        }

        // Get the JSON data from the file
        $jsonData = file_get_contents($this->filePath);
        if ($jsonData === false) {
            return [];
        }

        // Decode the JSON data into an array
        $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            return [];
        }

        // Convert each associative array entry into a Document object
        return array_map(function (array $entry): Document {
            $document = new Document();
            $document->content = $entry['content'] ?? '';
            $document->formattedContent = $entry['formattedContent'] ?? null;
            $document->embedding = $entry['embedding'] ?? null;
            $document->sourceType = $entry['sourceType'] ?? null;
            $document->sourceName = $entry['sourceName'] ?? null;
            $document->chunkNumber = $entry['chunkNumber'] ?? 0;
            $document->hash = $entry['hash'] ?? null;

            return $document;
        }, $data);
    }
}
