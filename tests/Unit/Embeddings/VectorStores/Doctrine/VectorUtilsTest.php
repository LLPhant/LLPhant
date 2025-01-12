<?php

namespace Tests\Unit\Embeddings\VectorStores\Doctrine;

use LLPhant\Embeddings\VectorStores\Doctrine\SupportedDoctrineVectorStore;
use LLPhant\Embeddings\VectorStores\Doctrine\VectorUtils;

describe('VectorUtils', function () {
    it('can convert an array floats into its DB representation', function () {
        $encoded = VectorUtils::getVectorAsString([3.14, 82.125, -23.456], SupportedDoctrineVectorStore::Postgres);
        expect($encoded)->toBe('[3.14,82.125,-23.456]');

        $encoded = VectorUtils::getVectorAsString([3.14, 82.125, -23.456], SupportedDoctrineVectorStore::MariaDB);
        expect($encoded)->toBe('[3.14,82.125,-23.456]');
    });
});
