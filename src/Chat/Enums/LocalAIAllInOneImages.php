<?php

namespace LLPhant\Chat\Enums;

/**
 * These are enums that can be used with LocalAI AllInOne docker images
 * See https://localai.io/basics/container/#all-in-one-images
 */
enum LocalAIAllInOneImages: string
{
    case TextGeneration = 'gpt-4';
    case MultiModalVision = 'gpt-4-vision-preview';
    case ImageGeneration = 'stablediffusion';
    case SpeechToText = 'whisper-1';
    case TextToSpeech = 'tts-1';
    case Embedding = 'text-embedding-ada-002';
    case Reranking = 'jina-reranker-v1-base-en';
}
