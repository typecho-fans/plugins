<?php
/**
 * Typecho update assistant.
 *
 * @package UpdateAssistant
 * @author  mrgeneral
 * @version 1.0.1
 * @link    https://www.chengxiaobai.cn
 */

class Downloader extends Base
{
    protected function down($isDevelop, $archiveName, $workRootPath)
    {
        $downloadUrl     = 'https://github.com/typecho/typecho/archive/master.zip';
        $archiveRealPath = rtrim($workRootPath, '/.\\') . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . $archiveName . '.zip';

        // cache
        if (is_file($archiveRealPath)) {
            return true;
        }

        if (!$isDevelop) {
            $content = $this->{$this->handler}('http://typecho.org/version.json');

            if (empty($content)
                || null === ($content = json_decode($content, true))
                || empty($content['release'])
                || empty($content['version'])
            ) {
                throw new Exception('Fetch release version failed!');
            }

            $downloadUrl = sprintf('https://github.com/typecho/typecho/archive/v%s-%s-release.zip', $content['release'], $content['version']);
        }

        return $this->{$this->handler}($downloadUrl, $archiveRealPath);
    }
}
