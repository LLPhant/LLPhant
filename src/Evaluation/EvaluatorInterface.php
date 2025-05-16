<?php

namespace LLPhant\Evaluation;

interface EvaluatorInterface
{
    /**
     * Generate a comprehensive evaluation report
     *
     * @return string HTML report
     */
    public function generateReport(): string;

    /**
     * Export evaluation results to JSON
     *
     * @return string JSON representation of evaluation results
     */
    public function exportResultsAsJson(): string;
}
