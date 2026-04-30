<?php

namespace LLPhant\Tool;

class HumanInTheLoopTool extends ToolBase
{
    /** @var callable(string): string */
    private $inputProvider;

    /**
     * @param  callable(string): string|null  $inputProvider  A callable that receives the question and returns the human's answer.
     *                                                        Defaults to reading from STDIN.
     */
    public function __construct(bool $verbose = false, ?callable $inputProvider = null)
    {
        parent::__construct($verbose);

        $this->inputProvider = $inputProvider ?? static function (string $question): string {
            echo $question.PHP_EOL;
            $handle = fopen('php://stdin', 'r');
            if ($handle === false) {
                throw new \RuntimeException('Could not open STDIN for reading.');
            }
            $line = fgets($handle);
            fclose($handle);
            if ($line === false) {
                throw new \RuntimeException('Could not read a line from STDIN.');
            }

            return trim($line);
        };
    }

    /**
     * Ask the user a clarifying question and return their answer.
     *
     * @param  string  $question  The clarifying question to present to the user
     */
    public function askUser(string $question): string
    {
        $answer = ($this->inputProvider)($question);
        $this->lastResponse = $answer;
        $this->wasSuccessful = true;

        return $answer;
    }
}
