<?php
/**
 * Typecho update assistant.
 *
 * @package UpdateAssistant
 * @author  mrgeneral
 * @version 1.0.1
 * @link    https://www.chengxiaobai.cn
 */

class Version extends Base
{

    protected function getVersion($isDevelop = true)
    {
        return $isDevelop ? $this->getDevelop() : $this->getRelease();
    }

    protected function getDevelop()
    {
        $content = $this->{$this->handler}(
            'https://raw.githubusercontent.com/typecho/typecho/master/var/Typecho/Common.php',
            '',
            [],
            ['Referer' => 'https://github.com/typecho/typecho/blob/master/var/Typecho/Common.php']
        );

        if (empty($content) || !preg_match('/const VERSION = \'(\w.*)\';/', $content, $result)) {
            throw new Exception('Fetch develop version failed!');
        }

        return $result[1];
    }

    protected function getRelease()
    {
        $content = $this->{$this->handler}('http://typecho.org/version.json');

        if (empty($content)
            || null === ($content = json_decode($content, true))
            || empty($content['release'])
            || empty($content['version'])
        ) {
            throw new Exception('Fetch release version failed!');
        }

        return sprintf('%s/%s', $content['release'], $content['version']);
    }

    protected function compare($currentVersion, $remoteVersion, $operator)
    {
        return version_compare(str_replace('/', '.', $currentVersion), str_replace('/', '.', $remoteVersion), $operator);
    }

    protected function toString($version)
    {
        return implode(array_map(function ($subVersion) {
            return sprintf('%02s', $subVersion);
        }, explode('.', str_replace('/', '.', $version))));
    }
}