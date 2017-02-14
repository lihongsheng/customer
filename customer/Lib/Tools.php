<?php
/**
 * Tools.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/13 下午10:58
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

class Tools
{
    /**
     * 判断是否来之命令行
     * @return bool
     */
    public static function isCli()
    {
        return (PHP_SAPI === 'cli' OR defined('STDIN'));
    }

    public static function removeInvisibleCharacters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded)
        {
            $non_displayables[] = '/%0[0-8bcef]/i';	// url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/i';	// url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do
        {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        }
        while ($count);

        return $str;
    }
}