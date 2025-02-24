<?php

namespace LLPhant\Embeddings\DataReader;

use LLPhant\Embeddings\Document;
use Smalot\PdfParser\Parser;

final class FileDataReader implements DataReader
{
    public string $sourceType = 'files';

    /**
     * @template T of Document
     *
     * @param  class-string<T>  $documentClassName
     * @param  string[]  $extensions
     */
    public function __construct(public string $filePath, public readonly string $documentClassName = Document::class, private readonly array $extensions = [])
    {
    }

    /**
     * @return Document[]
     */
    public function getDocuments(): array
    {
        if (! file_exists($this->filePath)) {
            return [];
        }

        // If it's a directory
        if (is_dir($this->filePath)) {
            return $this->getDocumentsFromDirectory($this->filePath);
        }
        // If it's a file
        $content = $this->getContentFromFile($this->filePath);
        if ($content === false) {
            return [];
        }

        return [$this->getDocument($content, $this->filePath)];
    }

    /**
     * @return Document[]
     */
    private function getDocumentsFromDirectory(string $directory): array
    {
        $documents = [];
        // Open the directory
        if ($handle = opendir($directory)) {
            // Read the directory contents
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

            // Close the directory
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

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'html'
            && class_exists('\Html2Text\Html2Text')
        ) {
            $html = new \Html2Text\Html2Text(file_get_contents($path));
            $fullText = $html->getText();
            return $fullText;
        }

        return file_get_contents($path);
    }

    private function getDocument(string $content, string $entry): mixed
    {
        $document = new $this->documentClassName();
        $document->content = $content;
        $document->sourceType = $this->sourceType;
        $document->sourceName = $entry;
        $document->hash = \hash('sha256', $content);

        return $document;
    }

    private function validExtension(string $fileExtension): bool
    {
        if ($this->extensions === []) {
            return true;
        }

        return in_array($fileExtension, $this->extensions);
    }
}
