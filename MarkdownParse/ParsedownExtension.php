<?php

require_once 'Parsedown.php';

class ParsedownExtension extends Parsedown
{
    protected $isTocEnabled      = false;
    protected $absoluteUrl       = '';
    protected $rawTocList        = [];
    protected $findTocSyntaxRule = '#^<p> *\[TOC\]\s*</p>$#m';

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

    public function setAbsoluteUrl($absoluteUrl)
    {
        $this->absoluteUrl = $absoluteUrl;

        return $this;
    }

    public function text($text)
    {
        $content = parent::text($text);

        if (!$this->isTocEnabled || empty($this->rawTocList) || !preg_match($this->findTocSyntaxRule, $content)) {
            return $content;
        }

        return preg_replace($this->findTocSyntaxRule, $this->buildToc(), $content);
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
}