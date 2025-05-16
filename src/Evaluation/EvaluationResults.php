<?php

namespace LLPhant\Evaluation;

class EvaluationResults
{
    /**
     * @param  float[]  $results
     */
    public function __construct(private readonly string $metricName, private readonly array $results)
    {
    }

    public function getMetricName(): string
    {
        return $this->metricName;
    }

    /**
     * @return float[]
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
