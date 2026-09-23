<?php

namespace LLPhant\Classification\Enums;

enum ChatRole: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
    case Function = 'function';
    case Tool = 'tool';
}
