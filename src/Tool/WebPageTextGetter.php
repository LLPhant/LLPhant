<?php

namespace LLPhant\Tool;

use Exception;
use LLPhant\Render\CLIOutputUtils;
use LLPhant\Render\OutputAgentInterface;

class WebPageTextGetter extends ToolBase
{
    /**
     * @throws Exception
     */
    public function __construct(bool $verbose = false, public OutputAgentInterface $outputAgent = new CLIOutputUtils())
    {
        parent::__construct($verbose);
    }

    /**
     * With this function you can get the content of multiple web pages by their URLs.
     *
     * @param  string[]  $urls
     * @return string[]
     *
     * @throws Exception
     */
    public function getMultipleWebPageText(array $urls): array
    {
        $this->outputAgent->renderTitleAndMessageOrange('🔧 retrieving web content of those pages :',
            implode(', ', $urls), true);
        $texts = [];
        foreach ($urls as $url) {
            $texts[$url] = $this->getWebPageText($url);
        }

        return $texts;
    }

    /**
     * With this function you can get content of a web page by its URL.
     *
     * @throws \Exception
     */
    public function getWebPageText(string $url): string
    {
        $this->outputAgent->renderTitleAndMessageOrange('🔧 retrieving web page content', $url, true);

        try {
            $html = \file_get_contents($url, false, \stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Mozilla/5.0\r\n",
                ],
            ]));
            if ($html === false) {
                throw new \Exception('Unable to retrieve web page content');
            }
            $text = (string) preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
            $text = (string) preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);
            $text = strip_tags($text);
            $text = html_entity_decode($text);
            $text = str_replace("\n", '.', $text);
            $text = str_replace("\t", '.', $text);
            $text = str_replace("\r", '.', $text);
            $text = (string) preg_replace('/( )+/', ' ', $text);

            return (string) preg_replace('/((\.)|( \.))+/', '.', $text);
        } catch (Exception) {
            return 'We couldn\'t retrieve the web page content from the url provided '.$url;
        }
    }
}
