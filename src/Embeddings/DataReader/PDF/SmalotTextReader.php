<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\DataReader\PDF;

use Smalot\PdfParser\Parser;

class SmalotTextReader implements PDFReader
{
    public static function getContent(string $absolutePath): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);

        return $pdf->getText();
    }
}
