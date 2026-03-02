<?php

namespace LLPhant\Embeddings\DataReader;

use LLPhant\Embeddings\Document;
use Smalot\PdfParser\Parser;

final class FileDataReader implements DataReader
{
    public string $sourceType = 'files';

    public function __construct(
        public string $filePath,
        public readonly string $documentClassName = Document::class,
        private readonly array $extensions = []
    ) {
    }

    public function getDocuments(): array
    {
        if (! file_exists($this->filePath)) {
            return [];
        }

        if (is_dir($this->filePath)) {
            return $this->getDocumentsFromDirectory($this->filePath);
        }

        $content = $this->getContentFromFile($this->filePath);
        if ($content === false) {
            return [];
        }

        return [$this->getDocument($content, $this->filePath)];
    }

    private function getDocumentsFromDirectory(string $directory): array
    {
        $documents = [];
        if ($handle = opendir($directory)) {
            while (($entry = readdir($handle)) !== false) {
                $fullPath = $directory.'/'.$entry;
                if ($entry != '.' && $entry != '..') {
                    if (is_dir($fullPath)) {
                        $documents = [...$documents, ...$this->getDocumentsFromDirectory($fullPath)];
                    } else {
                        $content = $this->getContentFromFile($fullPath);
                        if ($content !== false) {
                            $documents[] = $this->getDocument($content, $entry);
                        }
                    }
                }
            }
            closedir($handle);
        }

        return $documents;
    }

    private function getContentFromFile(string $path): string|false
    {
        $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! $this->validExtension($fileExtension)) {
            return false;
        }

        if ($fileExtension === 'pdf') {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);

            return $pdf->getText();
        }

        if ($fileExtension === 'docx') {
            $docxReader = new DocxReader();

            return $docxReader->getText($path);
        }

        return file_get_contents($path);
    }

    protected function getDocument(string $content, string $entry): Document
    {
        $document = new $this->documentClassName();
        $document->content = $content;
        $document->sourceType = $this->sourceType;
        $document->sourceName = $entry;
        $document->hash = \hash('sha256', $content);

        // Extract and add metadata
        $metadata = $this->extractMetadata($content);
        foreach ($metadata as $key => $value) {
            $document->addMetadata($key, $value);
        }

        return $document;
    }

    public function extractMetadata(string $content): array
    {
        $metadata = [];

        // Example logic: extract **Title** and **Category** from content
        if (preg_match('/\*\*Title:\*\* (.+)/', $content, $matches)) {
            $metadata['title'] = trim($matches[1]);
        }

        if (preg_match('/\*\*Category:\*\* (.+)/', $content, $matches)) {
            $metadata['category'] = trim($matches[1]);
        }

        return $metadata;
    }

    private function validExtension(string $fileExtension): bool
    {
        if ($this->extensions === []) {
            return true;
        }

        return in_array($fileExtension, $this->extensions);
    }
}
