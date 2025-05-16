<?php

namespace LLPhant\Evaluation\stringComparison;

use LLPhant\Evaluation\EvaluationResults;
use LLPhant\Evaluation\stringComparison\metric\BLEU;
use LLPhant\Evaluation\stringComparison\metric\METEOR;
use LLPhant\Evaluation\stringComparison\metric\ROUGE;

class StringComparisonEvaluator
{
    private readonly BLEU $bleu;

    private readonly ROUGE $rouge;

    private readonly METEOR $meteor;

    public function __construct()
    {
        $this->bleu = new BLEU();
        $this->rouge = new ROUGE();
        $this->meteor = new METEOR();
    }

    public function calculateBLEU(string $reference, string $candidate, int $n = 1): EvaluationResults
    {
        return $this->bleu->calculate($reference, $candidate, $n);
    }

    public function calculateROUGE(string $reference, string $candidate, int $n = 1): EvaluationResults
    {
        return $this->rouge->calculate($reference, $candidate, $n);
    }

    public function calculateMETEOR(string $reference, string $candidate, int $n = 1): EvaluationResults
    {
        return $this->meteor->calculate($reference, $candidate, $n);
    }
}
