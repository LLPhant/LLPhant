<?php

declare(strict_types=1);

namespace LLPhant\Evaluation\Trajectory;

use LLPhant\Chat\Message;
use LLPhant\Evaluation\AbstractEvaluator;
use LLPhant\Evaluation\EvaluationResults;
use LLPhant\Evaluation\Trajectory\Vocabulary\HarmfulKeywordsEn;
use LLPhant\Evaluation\Trajectory\Vocabulary\StopWordsEn;

/**
 * A class for evaluating AI agent outputs using trajectory evaluation techniques.
 * This evaluates the quality of AI responses across multiple steps of interaction
 * to assess overall performance and task completion.
 */
final class TrajectoryEvaluator extends AbstractEvaluator
{
    /** @var float[]|string[] */
    private array $evaluationMetrics;

    /** @var string[][][] */
    private array $trajectories = [];

    /** @var string[][][] */
    private array $groundTruth = [];

    /**
     * @param  float[]  $metrics  List of evaluation metrics to use
     * @param  float  $passingThreshold  Minimum score to consider a trajectory successful
     */
    public function __construct(array $metrics = [], private readonly float $passingThreshold = 0.7)
    {
        $this->evaluationMetrics = $metrics ?: [
            'factualAccuracy' => 1.0,
            'relevance' => 1.0,
            'completeness' => 1.0,
            'harmlessness' => 1.0,
        ];
    }

    /**
     * Add a new trajectory (sequence of agent interactions) for evaluation
     *
     * @param  string  $trajectoryId  Unique identifier for this trajectory
     * @param  string[][]  $interactions  Array of interaction objects (prompt/response pairs)
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
     * @param  string[][]  $groundTruth  Expected outputs or states
     */
    public function addGroundTruth(string $trajectoryId, array $groundTruth): self
    {
        $this->groundTruth[$trajectoryId] = $groundTruth;

        return $this;
    }

    public function evaluateText(string $candidate, string $reference = '', int $n = 1): EvaluationResults
    {
        $this->addTrajectory('task1', [
            [
                'prompt' => $reference,
                'response' => $candidate,
            ],
        ]);
        $results = $this->evaluateAll();

        return new EvaluationResults(
            'Trajectory evaluation result',
            $this->flattenResults($results)
        );
    }

    public function evaluateMessages(array $messages, array $references = [], int $n = 1): EvaluationResults
    {
        $assistantMessages = array_filter(array_map(
            fn (Message $message): ?string => $message->role->value === 'assistant' ? $message->content : null,
            $messages,
        ));

        $userMessages = array_filter(array_map(
            fn (Message $message): ?string => $message->role->value === 'user' ? $message->content : null,
            $messages,
        ));
        $trajectory = [];
        foreach ($userMessages as $idx => $userMessage) {
            $trajectory[] = [
                'prompt' => $userMessage,
                'response' => $assistantMessages[$idx],
            ];
        }

        $this->addTrajectory('task1', $trajectory);
        $results = $this->evaluateAll();

        return new EvaluationResults(
            'Trajectory evaluation result',
            $this->flattenResults($results)
        );
    }

    /**
     * Evaluate all trajectories
     *
     * @return ((float[]|float)[]|bool|float|int|string)[][] Evaluation results
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
     * @return array{trajectoryId: string, stepScores: array<int, float[]>, metricScores: float[], overallScore: float, passed: bool, interactionCount: int} Evaluation results for this trajectory
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
     * @param  mixed[]  $array
     * @return string[]
     */
    private function flattenResults(array $array, string $prefix = ''): array
    {
        $flat = [];

        foreach ($array as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'_'.$key;

            if (is_array($value)) {
                $flat += $this->flattenResults($value, $path);
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    /**
     * Evaluate a single interaction step
     *
     * @param  string[]  $interaction  The prompt/response pair
     * @param  string[]|null  $expectedOutput  Ground truth for this step
     * @return array{factualAccuracy: float, relevance: float, completeness: float, harmlessness: float} Scores for each metric
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
                $weightedSum += $score * (float) $this->evaluationMetrics[$metric];
            }
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Evaluate factual accuracy of response against ground truth
     *
     * @param  string  $response  AI response
     * @param  string[]|null  $expectedOutput  Ground truth
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
            if (stripos($response, $fact) !== false) {
                $matchCount++;
            }
        }

        return round($matchCount / $totalFacts, 2);
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

        return round($matchCount / count($promptKeywords), 2);
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

        return min(1.0, round($actualLength / $expectedMinLength, 2));
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

        foreach (HarmfulKeywordsEn::HARMFUL_KEYWORDS as $keyword) {
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
     * @return string[] List of keywords
     */
    private function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        foreach (StopWordsEn::STOP_WORDS as $word) {
            $text = preg_replace('/\b'.$word.'\b/', '', (string) $text);
        }

        // Extract words and filter empty entries
        $words = preg_split('/\W+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! $words) {
            return [];
        }

        return $words;
    }
}
