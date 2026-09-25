<?php

namespace LLPhant\Chat\Enums;

/**
 * see https://docs.mistral.ai/getting-started/models/models_overview/
 */
enum MistralAIChatModel: string
{
    case tiny = 'mistral-tiny';
    case small = 'mistral-small-latest';
    case medium = 'mistral-medium-latest';
    case large = 'mistral-large-latest';
    case pixtralLarge = 'pixtral-large-latest';
    case ministral3b = 'ministral-3b-latest';
    case ministral8b = 'ministral-8b-latest';
    case codestral = 'codestral-latest';
    case openMistralNemo = 'open-mistral-nemo';
    case openCodestralMamba = 'open-codestral-mamba';
    case mistralModeration = 'mistral-moderation-latest';
}
