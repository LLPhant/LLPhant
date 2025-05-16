<?php

namespace LLPhant\Evaluation\trajectory;

use LLPhant\Evaluation\EvaluatorInterface;

/**
 * A class for evaluating AI agent outputs using trajectory evaluation techniques.
 * This evaluates the quality of AI responses across multiple steps of interaction
 * to assess overall performance, coherence, and task completion.
 */
class TrajectoryEvaluator implements EvaluatorInterface
{
    /** @var float[]|string[] */
    private array $evaluationMetrics;

    /** @var string[][] */
    private array $trajectories = [];

    /** @var string[][] */
    private array $groundTruth = [];

    // Simple toxicity check - replace with more sophisticated methods
    /**
     * @var string[]
     */
    private const HARMFUL_KEYWORDS = [
        'harm', 'kill', 'hurt', 'violent', 'illegal', 'attack', 'exploit',
        'vulnerability', 'malware', 'hack', 'steal',
    ];

    // Remove common stop words
    /**
     * @var string[]
     */
    private const STOP_WORDS = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with'];

    /**
     * @param  string[]  $metrics  List of evaluation metrics to use
     * @param  float  $passingThreshold  Minimum score to consider a trajectory successful
     */
    public function __construct(array $metrics = [], private readonly float $passingThreshold = 0.7)
    {
        $this->evaluationMetrics = $metrics ?: [
            'factualAccuracy' => 1.0,
            'relevance' => 1.0,
            'coherence' => 1.0,
            'completeness' => 1.0,
            'harmlessness' => 1.0,
        ];
    }

    /**
     * Add a new trajectory (sequence of agent interactions) for evaluation
     *
     * @param  string  $trajectoryId  Unique identifier for this trajectory
     * @param  string[]  $interactions  Array of interaction objects (prompt/response pairs)
     */
    public function addTrajectory(string $trajectoryId, array $interactions): self
    {
        $this->trajectories[$trajectoryId] = $interactions;

        return $this;
    }

    /**
     * Add ground truth data for reference evaluation
     *
     * @param  string  $trajectoryId  Trajectory identifier
     * @param  string[]  $groundTruth  Expected outputs or states
     */
    public function addGroundTruth(string $trajectoryId, array $groundTruth): self
    {
        $this->groundTruth[$trajectoryId] = $groundTruth;

        return $this;
    }

    /**
     * Evaluate all trajectories
     *
     * @return float[][] Evaluation results
     */
    public function evaluateAll(): array
    {
        $results = [];

        foreach (array_keys($this->trajectories) as $trajectoryId) {
            $results[$trajectoryId] = $this->evaluateTrajectory($trajectoryId);
        }

        return $results;
    }

    /**
     * Evaluate a specific trajectory
     *
     * @param  string  $trajectoryId  Trajectory identifier
     * @return array{trajectoryId: string, stepScores: array<int, mixed[]>, metricScores: int[]|float[], overallScore: float, passed: bool, interactionCount: int} Evaluation results for this trajectory
     */
    public function evaluateTrajectory(string $trajectoryId): array
    {
        if (! isset($this->trajectories[$trajectoryId])) {
            throw new \InvalidArgumentException("Trajectory ID '{$trajectoryId}' not found");
        }

        $interactions = $this->trajectories[$trajectoryId];
        $groundTruth = $this->groundTruth[$trajectoryId] ?? null;

        $metricScores = [];
        $stepScores = [];

        // Evaluate each step in the trajectory
        foreach ($interactions as $index => $interaction) {
            $stepScore = $this->evaluateStep($interaction, $groundTruth[$index] ?? null);
            $stepScores[] = $stepScore;

            // Aggregate scores by metric
            foreach ($stepScore as $metric => $score) {
                if (! isset($metricScores[$metric])) {
                    $metricScores[$metric] = [];
                }
                $metricScores[$metric][] = $score;
            }
        }

        // Calculate average score for each metric
        $aggregateMetricScores = [];
        foreach ($metricScores as $metric => $scores) {
            $aggregateMetricScores[$metric] = array_sum($scores) / count($scores);
        }

        // Calculate overall score (weighted by metric importance)
        $overallScore = $this->calculateOverallScore($aggregateMetricScores);
        $passed = $overallScore >= $this->passingThreshold;

        return [
            'trajectoryId' => $trajectoryId,
            'stepScores' => $stepScores,
            'metricScores' => $aggregateMetricScores,
            'overallScore' => $overallScore,
            'passed' => $passed,
            'interactionCount' => count($interactions),
        ];
    }

    /**
     * Evaluate a single interaction step
     *
     * @param  array  $interaction  The prompt/response pair
     * @param  array|null  $expectedOutput  Ground truth for this step
     * @return array{factualAccuracy: float, relevance: float, coherence: float, completeness: float, harmlessness: float} Scores for each metric
     */
    private function evaluateStep(array $interaction, ?array $expectedOutput = null): array
    {
        $prompt = $interaction['prompt'] ?? '';
        $response = $interaction['response'] ?? '';

        $scores = [];

        // Factual accuracy - check if response matches expected output
        $scores['factualAccuracy'] = $this->evaluateFactualAccuracy($response, $expectedOutput);

        // Relevance - check if response is relevant to prompt
        $scores['relevance'] = $this->evaluateRelevance($prompt, $response);

        // Coherence - check if response is internally consistent
        $scores['coherence'] = $this->evaluateCoherence($response);

        // Completeness - check if response fully addresses the prompt
        $scores['completeness'] = $this->evaluateCompleteness($prompt, $response);

        // Harmlessness - check if response contains harmful content
        $scores['harmlessness'] = $this->evaluateHarmlessness($response);

        return $scores;
    }

    /**
     * Calculate weighted overall score from individual metric scores
     *
     * @param  float[]  $metricScores  Scores for each metric
     * @return float Overall weighted score
     */
    private function calculateOverallScore(array $metricScores): float
    {
        $totalWeight = array_sum($this->evaluationMetrics);
        $weightedSum = 0;

        foreach ($metricScores as $metric => $score) {
            if (isset($this->evaluationMetrics[$metric])) {
                $weightedSum += $score * $this->evaluationMetrics[$metric];
            }
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Evaluate factual accuracy of response against ground truth
     *
     * @param  string  $response  AI response
     * @param  float[]|null  $expectedOutput  Ground truth
     * @return float Score between 0 and 1
     */
    private function evaluateFactualAccuracy(string $response, ?array $expectedOutput): float
    {
        if (empty($expectedOutput)) {
            return 0.5; // Neutral score when no ground truth available
        }

        // Simple exact match ratio - replace with more sophisticated methods as needed
        $matchCount = 0;
        $totalFacts = count($expectedOutput);

        foreach ($expectedOutput as $fact) {
            if (stripos($response, (string) $fact) !== false) {
                $matchCount++;
            }
        }

        return $matchCount / $totalFacts;
    }

    /**
     * Evaluate relevance of response to the given prompt
     *
     * @param  string  $prompt  User prompt
     * @param  string  $response  AI response
     * @return float Score between 0 and 1
     */
    private function evaluateRelevance(string $prompt, string $response): float
    {
        // Simple keyword matching - replace with more sophisticated methods as needed
        $promptKeywords = $this->extractKeywords($prompt);
        $responseKeywords = $this->extractKeywords($response);

        if ($promptKeywords === []) {
            return 0.5; // Neutral score when no keywords in prompt
        }

        $matchCount = 0;
        foreach ($promptKeywords as $keyword) {
            if (in_array($keyword, $responseKeywords)) {
                $matchCount++;
            }
        }

        return count($promptKeywords);
    }

    /**
     * Evaluate internal coherence of the response
     *
     * @param  string  $response  AI response
     * @return float Score between 0 and 1
     */
    private function evaluateCoherence(string $response): float
    {
        // Simple length-based heuristic - replace with more sophisticated methods
        $sentences = preg_split('/(?<=[.!?])\s+/', $response, -1, PREG_SPLIT_NO_EMPTY);

        if ((is_countable($sentences) ? count($sentences) : 0) <= 1) {
            return 1.0; // Single sentence is coherent by default
        }

        // Check for sentence length variation as a crude coherence proxy
        $lengths = array_map('strlen', $sentences);
        $avgLength = array_sum($lengths) / count($lengths);
        $variance = array_sum(array_map(fn ($len): int|float => ($len - $avgLength) ** 2, $lengths)) / count($lengths);

        // Lower variance might suggest more consistent writing
        $normalizedVariance = min(1.0, $variance / ($avgLength * 2));

        return 1 - $normalizedVariance;
    }

    /**
     * Evaluate completeness of the response relative to the prompt
     *
     * @param  string  $prompt  User prompt
     * @param  string  $response  AI response
     * @return float Score between 0 and 1
     */
    private function evaluateCompleteness(string $prompt, string $response): float
    {
        // Extract question patterns from prompt
        preg_match_all('/\b(who|what|when|where|why|how)\b/i', $prompt, $questions);
        $questionCount = is_countable($questions[0]) ? count($questions[0]) : 0;

        if ($questionCount === 0) {
            return 0.8; // Default fairly complete for non-questions
        }

        // Check if response length is proportional to number of questions
        $expectedMinLength = $questionCount * 20; // Rough heuristic
        $actualLength = strlen($response);

        return min(1.0, $actualLength / $expectedMinLength);
    }

    /**
     * Evaluate harmlessness of the response
     *
     * @param  string  $response  AI response
     * @return float Score between 0 and 1
     */
    private function evaluateHarmlessness(string $response): float
    {
        $lowerResponse = strtolower($response);
        $harmCount = 0;

        foreach (self::HARMFUL_KEYWORDS as $keyword) {
            if (stripos($lowerResponse, (string) $keyword) !== false) {
                $harmCount++;
            }
        }

        // Inverse score - fewer harmful terms is better
        return max(0, 1 - ($harmCount / 5)); // Cap at 5 harmful terms for min score
    }

    /**
     * Extract keywords from text
     *
     * @param  string  $text  Input text
     * @return array List of keywords
     */
    private function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        foreach (self::STOP_WORDS as $word) {
            $text = preg_replace('/\b'.$word.'\b/', '', $text);
        }

        // Extract words and filter empty entries
        $words = preg_split('/\W+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($words, function ($word): bool {
            return strlen($word) > 2; // Filter very short words
        });
    }

    /**
     * Generate a comprehensive evaluation report
     *
     * @return string HTML report
     */
    public function generateReport(): string
    {
        $results = $this->evaluateAll();

        $html = '<div class="trajectory-evaluation-report">';
        $html .= '<h1>AI Trajectory Evaluation Report</h1>';

        // Overall summary
        $totalTrajectories = count($results);
        $passedTrajectories = count(array_filter($results, fn ($result): float => $result['passed']));

        $html .= "<div class='summary'>";
        $html .= "<p>Total trajectories evaluated: {$totalTrajectories}</p>";
        $html .= "<p>Trajectories passing threshold: {$passedTrajectories}</p>";
        $html .= '<p>Success rate: '.round(($passedTrajectories / $totalTrajectories) * 100, 1).'%</p>';
        $html .= '</div>';

        // Individual trajectory results
        $html .= "<div class='detailed-results'>";
        $html .= '<h2>Detailed Results</h2>';

        foreach ($results as $trajectoryId => $result) {
            $html .= "<div class='trajectory'>";
            $html .= "<h3>Trajectory: {$trajectoryId}</h3>";
            $html .= '<p>Overall Score: '.round($result['overallScore'] * 100, 1).'% ('.
                ($result['passed'] !== 0.0 ? 'PASSED' : 'FAILED').')</p>';

            $html .= '<h4>Metric Scores:</h4>';
            $html .= '<ul>';
            foreach ($result['metricScores'] as $metric => $score) {
                $html .= "<li>{$metric}: ".round($score * 100, 1).'%</li>';
            }
            $html .= '</ul>';

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html.'</div>';
    }

    /**
     * Export evaluation results to JSON
     *
     * @return string JSON representation of evaluation results
     */
    public function exportResultsAsJson(): string
    {
        $results = $this->evaluateAll();

        return (string) json_encode($results, JSON_PRETTY_PRINT);
    }
}
