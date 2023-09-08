<?php

namespace LLPhant\Experimental\Agent;

use LLPhant\Chat\Function\FunctionInfo;
use LLPhant\Chat\Function\Parameter;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Utils\CLIOutputUtils;

class CreationTaskAgent extends AgentBase
{
    private readonly OpenAIChat $openAIChat;

    public function __construct(private readonly TaskManager $taskManager, OpenAIChat $openAIChat = null, bool $verbose = false)
    {
        parent::__construct($verbose);
        $this->openAIChat = $openAIChat ?? new OpenAIChat();
        $nameTask = new Parameter('name', 'string', 'name of the task');
        $descriptionTask = new Parameter('description', 'string', 'description of the task');
        $param = new Parameter('tasks', 'array', 'tasks to be added to the list of tasks to be completed', [], null, [$nameTask, $descriptionTask]);
        $addTasksFunction = new FunctionInfo('addTasks', $this->taskManager, 'add tasks to the list of tasks to be completed', [$param], [$param]);
        $this->openAIChat->addFunction($addTasksFunction);
        $this->openAIChat->requiredFunction = $addTasksFunction;
    }

    /**
     * Generates new tasks using OpenAI API based on previous tasks' results.
     */
    public function createTasks(string $objective): void
    {
        // Join the task list into a string for the prompt
        $unachievedTasks = implode(', ', array_column($this->taskManager->getUnachievedTasks(), 'name'));

        if (empty($this->taskManager->getAchievedTasks())) {
            $prompt = 'You are a task creation AI that uses the result of an execution agent. '
                ."The objective is: {$objective},"
                .' No task has been done yet.'
                ." These are incomplete tasks: {$unachievedTasks}."
                .' Based on the result, create new tasks to be completed only if needed.';
        } else {
            $achievedTasks = $this->taskManager->getAchievedTasksNameAndResult();
            $prompt = 'You are a task creation AI that uses the result of an execution agent'
                ."Your objective is: {$objective},"
                ." The previous tasks are: {$achievedTasks}."
                ." These are incomplete tasks: {$unachievedTasks}."
                .' Based on the result of previous tasks, create new tasks to do the objective but ONLY if needed.'
                .' You MUST avoid create duplicated tasks.';
        }
        CLIOutputUtils::renderTitleAndMessageGreen('🤖 CreationTaskAgent.', 'Prompt: '.$prompt, $this->verbose);

        // We don't handle the response because the function will be executed
        $this->openAIChat->generateText($prompt);
    }
}
