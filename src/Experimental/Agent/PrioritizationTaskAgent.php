<?php

namespace LLPhant\Experimental\Agent;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Utils\CLIOutputUtils;

class PrioritizationTaskAgent extends AgentBase
{
    private readonly OpenAIChat $openAIChat;

    public function __construct(private readonly TaskManager $taskManager, OpenAIChat $openAIChat = null, bool $verbose = false)
    {
        parent::__construct($verbose);
        $this->openAIChat = $openAIChat ?? new OpenAIChat();
    }

    public function prioritizeTask(string $objective): ?Task
    {
        if (count($this->taskManager->getUnachievedTasks()) <= 1) {
            return $this->taskManager->getNextTask();
        }

        $unachievedTasks = '';
        foreach ($this->taskManager->getUnachievedTasks() as $key => $task) {
            $unachievedTasks .= "id:{$key} name: {$task->name}.";
        }
        $achievedTasks = $this->taskManager->getAchievedTasksNameAndResult();

        // Prepare the prompt using the provided information
        $prompt = "Consider the ultimate objective of your team: {$objective}.
                You are a task prioritization AI tasked with reprioritizing the following tasks: {$unachievedTasks}."
            ." To help you the previous tasks are: {$achievedTasks}."
            .' Return the id of the next task that will bring us closer to achieve the objective';

        CLIOutputUtils::renderTitleAndMessageGreen('🤖 PrioritizationTaskAgent.', 'Prompt: '.$prompt, $this->verbose);

        $response = $this->openAIChat->generateText($prompt);

        CLIOutputUtils::renderTitleAndMessageGreen('🤖 PrioritizationTaskAgent.', 'Response: '.$response, $this->verbose);

        // Look for the first number in the response
        if (preg_match('/\d+/', $response, $matches)) {
            $firstNumber = $matches[0];
            if (isset($this->taskManager->getUnachievedTasks()[$firstNumber])) {

                return $this->taskManager->getUnachievedTasks()[$firstNumber];
            }
        }

        return $this->taskManager->getNextTask();
    }
}
