<?php
namespace Jsnlib\Upload;

class Format
{
    /**
     * 上傳成功回傳的格式
     * @param  string  $newname
     * @param  string  $path
     * @param  string  $url       帶入指定網址，可以是非上傳的實際路徑
     * @return array
     */
    public function back($newname, $path, $url = null)
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

    public function newNameString($prefix)
    {
        $this->rand           = new \Jsnlib\Rand();
        
        return $prefix . "_" . $this->rand->get(4, [2])[0] . "_" . time();
    }

    /**
     * 組合路徑與檔案完整名稱的字串
     * @param   $newname 
     * @param   $site    指定位置
     */
    public function mixPathAndFilename($newname, $site)
    {
        //檢查並自動幫site結尾補上/
        $siteSlash = $this->keepEndSlash($site);

        return $siteSlash . $newname;
    }

    //讓字串結尾保持 「 / 」
    private function keepEndSlash($string)
    {
        $endstr = substr($string, -1);
        $token  = "/";

        if ($endstr == $token)
        {
            unset($token);
            return $string;
        }

        return $string . $token;
    }

    //上傳檔案的副檔名  $Lower=true全部轉成小寫
    public function filenameExtension($filename, $arykey, $lower = true)
    {
        $name        = $_FILES[$filename]['name'][$arykey];
        $pinfo       = pathinfo($name);
        $filenameExt = $pinfo['extension'];

        if ($lower == true)
        {
            $filenameExt = strtolower($filenameExt);
        }

        //小寫
        return $filenameExt;
    }
}
