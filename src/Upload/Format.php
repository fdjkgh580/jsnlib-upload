<?php
namespace Jsnlib\Upload;

class Format
{
    /**
     * 上傳成功回傳的格式
     * @param  string  $newname
     * @param  string  $path
     * @param  string  $url 帶入指定網址，可以是非上傳的實際路徑
     * @return array
     */
    public static function back($newname, $path, $url = null)
    {
        if (empty($newname))
        {
            throw new \Exception('未指定新的檔名');
        }

        // 回傳格式
        $back             = [];
        $back['filename'] = $newname;
        $back['path']     = $path;

        // 若指定網址
        if (isset($url))
        {
            $back['url'] = trim($url, "\ /") . "/" . $path;
        }

        return $back;

    }
}
