<?php

namespace LLPhant\Evaluation;

class EvaluationResults
{
    /**
     * @param  (float|bool|string)[]  $results
     */
    public function __construct(private readonly string $metricName, private readonly array $results)
    {
    }

    public function getMetricName(): string
    {
        return $this->metricName;
    }

    /**
     * @return (float|bool|string)[]
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
