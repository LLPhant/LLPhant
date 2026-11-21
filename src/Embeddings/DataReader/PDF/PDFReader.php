<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\DataReader\PDF;

interface PDFReader
{
    public static function getContent(string $absolutePath): string;
}
