<?php

declare(strict_types=1);

namespace Tests\Integration\Tool;

use LLPhant\Tool\SerpApiSearch;

it('can perform SERP queries', function () {
    $serpSearches = new SerpApiSearch();
    $answer = $serpSearches->googleSearch('What is the most important book written by Giovanni Boccaccio?');
    expect($answer)->toContain('Decameron');
});
