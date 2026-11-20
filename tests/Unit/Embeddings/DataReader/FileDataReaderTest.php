<?php

declare(strict_types=1);

namespace Tests\Unit\Embeddings\DataReader;

use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DataReader\PDF\MarkdownReader;
use LLPhant\Embeddings\DataReader\PDF\PrinsFrankTextReader;
use LLPhant\Embeddings\Document;

it('can read various types of documents', function (string $docName, string $startingContent) {
    $filePath = __DIR__.'/FilesTestDirectory/'.$docName;
    $reader = new FileDataReader($filePath);
    $documents = $reader->getDocuments();

    expect($documents[0]->content)->toStartWith($startingContent);
})->with([
    ['hello.txt', "hello test!\n"],
    ['data.docx', 'This data is from a docx'],
    ['document-with-text-breaks.docx', 'Sample document with text breaks'],
    ['simple_document_with_links.docx', 'This is a doc with links'],
    ['data-pdf.pdf', 'This data is from a pdf'],
]);

it('computes the hash of the content', function (string $docName) {
    $filePath = __DIR__.'/FilesTestDirectory/'.$docName;
    $reader = new FileDataReader($filePath);
    $documents = $reader->getDocuments();

    expect($documents[0]->hash)->toBe(\hash('sha256', $documents[0]->content));
})->with([
    'hello.txt', 'hello2.txt',
]);

it('can read pdf and texts ', function () {
    $filePath = __DIR__.'/FilesTestDirectory/';
    $reader = new FileDataReader($filePath);
    $documents = $reader->getDocuments();

    $foundPDF = false;
    $foundText = false;
    foreach ($documents as $document) {
        if ($document->content === 'This data is from a pdf') {
            $foundPDF = true;
        }
        if ($document->content === "hello test!\n") {
            $foundText = true;
        }
    }

    expect($foundPDF)->toBeTrue();
    expect($foundText)->toBeTrue();
});

it('can read pdf with alternative library', function () {
    $filePath = __DIR__.'/FilesTestDirectory/data-pdf.pdf';
    $reader = new FileDataReader($filePath, pdfReader: PrinsFrankTextReader::class);
    $documents = $reader->getDocuments();
    expect($documents)->toHaveCount(1)
        ->and($documents[0]->content)->toEqual('This data is from a pdf');
});

it('can read pdf as markdown', function () {
    $filePath = __DIR__.'/FilesTestDirectory/simplemarkdown.pdf';
    $reader = new FileDataReader($filePath, pdfReader: MarkdownReader::class);
    $documents = $reader->getDocuments();
    expect($documents)->toHaveCount(1)
        ->and($documents[0]->content)->toEqual("**Bold text**\n*Italic text*\n***Bold and italic text***\n[Link](http://Example.com/)");
});

it('can filter files based on extensions', function () {
    $filePath = __DIR__.'/FilesTestDirectory/';
    $reader = new FileDataReader($filePath, Document::class, ['docx']);
    $documents = $reader->getDocuments();

    expect($documents)->toHaveCount(3);
});

it('can read sub-directories', function () {
    $filePath = __DIR__.'/FilesTestDirectory/';
    $reader = new FileDataReader($filePath, Document::class, ['txt']);
    $documents = $reader->getDocuments();

    $contents = array_map(fn ($doc) => $doc->content, $documents);

    expect($contents)->toContain("hello test!\n", "hello test2!\n", "hello test3!\n");
});

it('can read docx in French', function () {
    $filePath = __DIR__.'/Candide.docx';
    $reader = new FileDataReader($filePath, Document::class, ['docx']);
    $documents = $reader->getDocuments();
    expect($documents)->toHaveCount(1)
        ->and($documents[0]->content)->toEqual("Candide ou l'Optimisme est un conte philosophique de Voltaire paru à Genève en janvier 1759.");
});

it('can read docx preserving new lines', function () {
    $filePath = __DIR__.'/Divina_Commedia.docx';
    $reader = new FileDataReader($filePath, Document::class, ['docx']);
    $documents = $reader->getDocuments();
    $text = <<<'TXT'
    Inferno, I canto, vv.1-3
    Nel mezzo del cammin di nostra vita
    mi ritrovai per una selva oscura,
    ché la diritta via era smarrita.
    Inferno, XXVI canto, vv.118-120
    Considerate la vostra semenza:
    fatti non foste a viver come bruti,
    ma per seguir virtute e canoscenza.
    TXT;
    expect($documents)->toHaveCount(1)
        ->and($documents[0]->content)->toEqual($text);
});
