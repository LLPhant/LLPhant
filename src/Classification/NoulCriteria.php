<?php

namespace LLPhant\Classification;

class NoulCriteria
{
    public function __construct(public readonly string $true, public readonly string $false)
    {
    }
}
