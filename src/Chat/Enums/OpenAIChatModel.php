<?php

namespace LLPhant\Chat\Enums;

enum OpenAIChatModel: string
{
    case Gpt35Turbo = 'gpt-3.5-turbo';

    case Gpt4 = 'gpt-4';
    case Gpt4Turbo = 'gpt-4-turbo';
    case Gpt4Omni = 'gpt-4o';
    case Gpt4OmniMini = 'gpt-4o-mini';
    case Gpt41 = 'gpt-4.1';
    case Gpt41Nano = 'gpt-4.1-nano';
    case Gpt41Mini = 'gpt-4.1-mini';
    case Gpt41Turbo = 'gpt-4.1-turbo';

    case Gpt5 = 'gpt-5';
    case Gpt5Pro = 'gpt-5-pro';
    case Gpt5Nano = 'gpt-5-nano';
    case Gpt5Mini = 'gpt-5-mini';
    case Gpt51 = 'gpt-5.1';
}
