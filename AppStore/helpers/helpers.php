<?php

/**
 * Delete Files
 *
 * Deletes all files contained in the supplied directory path.
 * Files must be writable or owned by the system in order to be deleted.
 * If the second parameter is set to TRUE, any directories contained
 * within the supplied base directory will be nuked as well.
 *
 * @param	string	$path		File path
 * @param	bool	$del_dir	Whether to delete any directories found in the path
 * @param	bool	$htdocs		Whether to skip deleting .htaccess and index page files
 * @param	int	$_level		Current directory depth level (default: 0; internal use only)
 * @return	bool
 */
function delete_files($path, $del_dir = true, $htdocs = false, $_level = 0)
{
    // Trim the trailing slash
    $path = rtrim($path, '/\\');

    if ( ! $current_dir = @opendir($path))
    {
        return FALSE;
    }

    while (FALSE !== ($filename = @readdir($current_dir)))
    {
        if ($filename !== '.' && $filename !== '..')
        {
            if (is_dir($path.DIRECTORY_SEPARATOR.$filename) && $filename[0] !== '.')
            {
                delete_files($path.DIRECTORY_SEPARATOR.$filename, $del_dir, $htdocs, $_level + 1);
            }
            elseif ($htdocs !== TRUE OR ! preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i', $filename))
            {
                @unlink($path.DIRECTORY_SEPARATOR.$filename);
            }
        }
    }

    closedir($current_dir);

    return ($del_dir === true && $_level > 0)
        ? @rmdir($path)
        : true;
}

/**
 * Http Get Request
 *
 * @param $url
 * @return string
 */
function http_get($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Referer: '.$_SERVER['SERVER_NAME']
    ));
    $out = curl_exec($ch);
    curl_close($ch);
    return $out;
}