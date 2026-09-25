<?php

namespace LLPhant\Chat\Enums;

enum OpenAIChatModel: string
{
    case Gpt35Turbo = 'gpt-3.5-turbo';
    case Gpt4 = 'gpt-4';
    case Gpt4Turbo = 'gpt-4-turbo';
    case Gpt4Omni = 'gpt-4o';
    case Gpt4Omini = 'gpt-4o-mini';
}
