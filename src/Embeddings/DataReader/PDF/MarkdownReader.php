<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\DataReader\PDF;

use PrinsFrank\PdfParser\PdfParser;

class MarkdownReader implements PDFReader
{
    public static function getContent(string $absolutePath): string
    {
        return (new PdfParser())
            ->parseFile($absolutePath)
            ->getMarkdown();
    }
}
