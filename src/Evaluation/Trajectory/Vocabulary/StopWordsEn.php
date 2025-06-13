<?php

declare(strict_types=1);

namespace LLPhant\Evaluation\Trajectory\Vocabulary;

final class StopWordsEn
{
    final public const STOP_WORDS = [
        // articles & basic conjunctions
        'a', 'an', 'the', 'and', 'or', 'but',

        // simple prepositions you already had
        'in', 'on', 'at', 'to', 'for', 'with',

        // additional prepositions & logical connectors
        'about', 'above', 'after', 'against', 'along', 'among', 'around',
        'before', 'behind', 'below', 'beneath', 'beside', 'between', 'beyond',
        'by', 'despite', 'during', 'except', 'inside', 'into', 'like', 'near',
        'off', 'onto', 'out', 'outside', 'over', 'past', 'since', 'through',
        'throughout', 'toward', 'under', 'underneath', 'until', 'up', 'upon',
        'via', 'within', 'without',

        // pronouns
        'i', 'me', 'my', 'myself',
        'we', 'us', 'our', 'ours', 'ourselves',
        'you', 'your', 'yours', 'yourself', 'yourselves',
        'he', 'him', 'his', 'himself',
        'she', 'her', 'hers', 'herself',
        'it', 'its', 'itself',
        'they', 'them', 'their', 'theirs', 'themselves',

        // auxiliary & modal verbs
        'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'having',
        'do', 'does', 'did', 'doing',
        'can', 'could', 'should', 'would', 'may', 'might', 'must', 'shall', 'will',

        // adverbs & qualifiers
        'again', 'almost', 'already', 'also', 'although', 'always', 'any',
        'anyhow', 'anyone', 'anything', 'anyway', 'anywhere',
        'both', 'each', 'either', 'enough', 'ever', 'every', 'everyone',
        'everything', 'everywhere', 'few', 'further', 'here', 'how',
        'however', 'just', 'least', 'less', 'many', 'more', 'most', 'much',
        'neither', 'never', 'no', 'nobody', 'none', 'nor', 'not', 'nothing',
        'now', 'nowhere', 'once', 'only', 'other', 'others', 'otherwise',
        'quite', 'rather', 'really', 'same', 'several', 'since', 'some',
        'somehow', 'someone', 'something', 'sometimes', 'somewhere', 'still',
        'such', 'than', 'then', 'there', 'therefore', 'these', 'those', 'though',
        'too', 'very', 'well', 'what', 'whatever', 'when', 'whenever', 'where',
        'wherever', 'whether', 'which', 'whichever', 'while', 'whither', 'who',
        'whoever', 'whom', 'whose', 'why', 'yet',
    ];
}
