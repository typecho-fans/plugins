<?php

require_once 'Parsedown.php';

class ParsedownExtension extends Parsedown
{
    protected $isTocEnabled           = false;
    protected $rawTocList             = [];
    protected $findTocSyntaxRule      = '#^<p>\s*\[TOC\]\s*</p>$#m';
    protected $isOriginalBlockEnabled = false;
    protected $originalBlockRuleList  = ['$' => '/\${1,2}[^`]*\${1,2}/m'];
    protected $absoluteUrl            = '';

    public function setTocEnabled($isTocEnable)
    {
        $this->isTocEnabled = $isTocEnable;

        return $this;
    }

    public function setTocSyntaxRule($findTocSyntaxRule)
    {
        $this->findTocSyntaxRule = $findTocSyntaxRule;

        return $this;
    }

    public function setIsOriginalBlockEnabled($isOriginalBlockEnabled)
    {
        $this->isOriginalBlockEnabled = $isOriginalBlockEnabled;

        return $this;
    }

    public function addOriginalBlockRule($originalBlockRule)
    {
        $this->originalBlockRuleList = array_merge($this->originalBlockRuleList, $originalBlockRule);

        return $this;
    }

    public function setAbsoluteUrl($absoluteUrl)
    {
        $this->absoluteUrl = $absoluteUrl;

        return $this;
    }

    public function text($text)
    {
        return $this->handleAfter(parent::text($this->handleBefore($text)));
    }

    protected function handleBefore($text)
    {
        array_map(function ($originalBlockMark) {

            $this->addInlineElements($originalBlockMark, ['Original']);

        }, array_keys($this->originalBlockRuleList));

        return $text;
    }

    protected function handleAfter($text)
    {
        if (!$this->isTocEnabled || empty($this->rawTocList) || !preg_match($this->findTocSyntaxRule, $text)) {
            return $text;
        }

        return preg_replace($this->findTocSyntaxRule, $this->buildToc(), $text);
    }

    protected function buildToc()
    {
        $tocMarkdownContent = '';
        $topHeadLevel       = min(array_column($this->rawTocList, 'level'));

        foreach ($this->rawTocList as $id => $tocItem) {
            $tocMarkdownContent .= sprintf('%s- [%s](%s#%s)' . PHP_EOL, str_repeat('  ', $tocItem['level'] - $topHeadLevel), $this->line($tocItem['text']), $this->absoluteUrl, $id);
        }

        $this->rawTocList = [];

        return parent::text($tocMarkdownContent);
    }

    public function addInlineElements($inlineMarker, $inlineHandleList)
    {
        if (strpos($this->inlineMarkerList, $inlineMarker) === false) {
            $this->inlineMarkerList .= $inlineMarker;
        }

        $this->InlineTypes[$inlineMarker] = array_merge(isset($this->InlineTypes[$inlineMarker]) ? $this->InlineTypes[$inlineMarker] : [], is_array($inlineHandleList) ? $inlineHandleList : (array)$inlineHandleList);

        return $this;
    }

    public function addBlockElements($blockMarker, $blockHandleList)
    {
        $this->BlockTypes[$blockMarker] = array_merge(isset($this->BlockTypes[$blockMarker]) ? $this->BlockTypes[$blockMarker] : [], is_array($blockHandleList) ? $blockHandleList : (array)$blockHandleList);

        return $this;
    }

    protected function blockHeader($line)
    {
        $block = parent::blockHeader($line);
        $text  = $block['element']['handler']['argument'];
        $id    = urlencode($this->line($text));

        $block['element']['attributes'] = [
            'id' => $id,
        ];

        $this->rawTocList[$id] = [
            'text'  => $text,
            'level' => str_replace('h', '', $block['element']['name']),
        ];

        return $block;
    }

    /**
     * parsing for original block
     *
     * @see https://github.com/mrgeneralgoo/typecho-markdown/pull/7
     *
     * @param $excerpt
     *
     * @return array|null
     */
    protected function inlineOriginal($excerpt)
    {

        $originalBlockMark = substr($excerpt['text'], 0, 1);

        if (!isset($this->originalBlockRuleList[$originalBlockMark]) || !preg_match($this->originalBlockRuleList[$originalBlockMark], $excerpt['text'], $originalBlock) || empty($originalBlock[0])) {
            return null;
        }

        return $this->inlineText($originalBlock[0]);
    }
}
