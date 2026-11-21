<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\DataReader\PDF;

use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\PdfParser;

class MarkdownReader implements PDFReader
{
    public static function getContent(string $absolutePath): string
    {
        /** @phpstan-ignore function.alreadyNarrowedType (as v2 of prinsfrank/pdfparser doesn't have markdown support yet) */
        if (! method_exists(Document::class, 'getMarkdown')) {
            throw new \Exception('To extract markdown content you need to have v3 or newer of "prinsfrank/pdfparser", which requires PHP 8.2 or higher. Please run "composer require prinsfrank/pdfparser:^4" or use plain text extraction');
        }

        return (new PdfParser())
            ->parseFile($absolutePath)
            ->getMarkdown();
    }
}
