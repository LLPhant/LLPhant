<?php

declare(strict_types=1);

namespace Tests\Unit\Embeddings\DataReader;

use LLPhant\Embeddings\DataReader\FileDataReader;
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

it('extracts metadata correctly from content', function () {
    $content = "**Title:** Test Title\n**Category:** Test Category\nSample content.";
    $reader = new FileDataReader('path/to/nonexistent/file'); // Pass a dummy path
    $metadata = $reader->extractMetadata($content);

    expect($metadata)
        ->toHaveKeys(['title', 'category'])
        ->and($metadata['title'])->toEqual('Test Title')
        ->and($metadata['category'])->toEqual('Test Category');
});

it('includes metadata in the document structure', function () {
    $content = "**Title:** Test Title\n**Category:** Test Category\nSample content.";
    $reader = new FileDataReader('path/to/nonexistent/file'); // Pass a dummy path

    // Use Reflection to access the private method
    $reflection = new ReflectionClass(FileDataReader::class);
    $method = $reflection->getMethod('getDocument');
    $method->setAccessible(true);
    $document = $method->invokeArgs($reader, [$content, 'test.txt']);

    $documentArray = $document->toArray();

    expect($documentArray)
        ->toHaveKeys(['content', 'metadata'])
        ->and($documentArray['metadata'])->toBeArray()
        ->and($documentArray['metadata']['title'])->toEqual('Test Title')
        ->and($documentArray['metadata']['category'])->toEqual('Test Category');
});

