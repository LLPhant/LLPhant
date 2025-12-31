<?php

declare(strict_types=1);

namespace Tests\Unit\Embeddings\DocumentSplitter;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;

it('splits a document by max length without separator', function () {
    $document = new Document();
    $document->content = 'This is a test';
    $result = DocumentSplitter::splitDocument($document, 3);
    expect($result[0]->content)->toBe('This');
    expect($result[1]->content)->toBe('is');
});

it('splits a document by max length with separator', function () {
    $document = new Document();
    $document->content = 'This-is-a-test';
    $result = DocumentSplitter::splitDocument($document, 11, '-');
    expect($result[0]->content)->toBe('This-is-a');
    expect($result[1]->content)->toBe('test');
});

it('returns the whole document if max length is greater than content', function () {
    $document = new Document();
    $document->content = 'This is a test';
    $result = DocumentSplitter::splitDocument($document, 50);
    expect($result[0]->content)->toBe('This is a test');
});

it('splits multiple documents', function () {
    $document1 = new Document();
    $document1->content = 'This is a test.';
    $document2 = new Document();
    $document2->content = 'Hello World!';
    $result = DocumentSplitter::splitDocuments([$document1, $document2], 5);
    expect($result[0]->content)->toBe('This is a test');
    expect($result[1]->content)->toBe('Hello World!');
});

it('splits texts with \n in it', function () {
    $document1 = new Document();
    $document1->content = 'Burritos are cool


France (French: [fʁɑ̃s] Listen), officially the French Republic (French: République française [ʁepyblik fʁɑ̃sɛz]),
[14] is a country located primarily in Western Europe.
It also includes overseas regions and territories in the Americas and the Atlantic,
Pacific and Indian Oceans,[XII] giving it one of the largest discontiguous exclusive economic zones in the world.


The house is on fire';
    $result = DocumentSplitter::splitDocument($document1, 100, "\n");
    expect($result[0]->content)->toBe('Burritos are cool');
});

it('adds overlap when splitting documents', function () {
    $document = new Document();
    $document->content = 'This is a test with one overlapping word';
    $result = DocumentSplitter::splitDocument($document, 20, ' ', 1);
    expect($result[0]->content)->toBe('This is a test with');
    expect($result[1]->content)->toBe('with one overlapping');
    expect($result[2]->content)->toBe('overlapping word');

    expect($result[0]->chunkNumber)->toBe(0);
    expect($result[1]->chunkNumber)->toBe(1);
    expect($result[2]->chunkNumber)->toBe(2);

    $document = new Document();
    $document->content = 'This is a test with two overlapping words';
    $result = DocumentSplitter::splitDocument($document, 20, ' ', 2);
    expect($result[0]->content)->toBe('This is a test with');
    expect($result[1]->content)->toBe('test with two');
    expect($result[2]->content)->toBe('with two overlapping');
    expect($result[3]->content)->toBe('two overlapping words');
});

it('adds overlap when splitting multiple documents', function () {
    $document1 = new Document();
    $document1->content = 'This. Is. A. Test. With. Overlapping. Words.';
    $document2 = new Document();
    $document2->content = 'Another. Test. With. Overlapping. Words.';
    $result = DocumentSplitter::splitDocuments([$document1, $document2], 20, '.', 1);
    expect($result[0]->content)->toBe('This. Is. A. Test');
    expect($result[1]->content)->toBe('Test. With');
    expect($result[2]->content)->toBe('With. Overlapping');
    expect($result[3]->content)->toBe('Overlapping. Words');
    expect($result[4]->content)->toBe('Another. Test. With');
    expect($result[5]->content)->toBe('With. Overlapping');
    expect($result[6]->content)->toBe('Overlapping. Words');
});

it('removes at least one word when overlapping', function () {
    $document = new Document();
    $document->content = 'This is a test with one overlapping word';
    $result = DocumentSplitter::splitDocument($document, 30, ' ', 20);
    expect($result[0]->content)->toBe('This is a test with one');
    expect($result[1]->content)->toBe('is a test with one overlapping');
    expect($result[2]->content)->toBe('a test with one overlapping word');
});

it('ignores overlap if <= 0', function () {
    $document = new Document();
    $document->content = 'This is a test';
    $result = DocumentSplitter::splitDocument($document, 10, ' ', 0);
    expect($result[0]->content)->toBe('This is a');
    expect($result[1]->content)->toBe('test');

    $document = new Document();
    $document->content = 'This is a test';
    $result = DocumentSplitter::splitDocument($document, 10, ' ', -1);
    expect($result[0]->content)->toBe('This is a');
    expect($result[1]->content)->toBe('test');
});

it('keeps separator when keepSeparator is true', function () {
    $document = new Document();
    $document->content = 'This is a test';
    $result = DocumentSplitter::splitDocument($document, 10, 'a', keepSeparator: true);
    expect($result[0]->content)->toBe('This is a');
    expect($result[1]->content)->toBe('test');
});
