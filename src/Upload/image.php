<?php
namespace Jsnlib\Upload;

class Image
{
    protected $check;

    public function __construct()
    {
        $this->check  = new \Jsnlib\Upload\Check();
        $this->format = new \Jsnlib\Upload\format();
    }

    /**
     * 重新調整大小
     * @param $param['imageResizeScriptPath']
     * @param $param['filename']
     * @param $param['arraykey']
     * @param $param['newname']
     * @param $param['site']
     * @param $param['resize_width']
     * @param $param['resize_height']
     * @param $param['resize_type']
     * @param $param['resize_quality']
     */
    public function resize($param)
    {
        include_once $param['imageResizeScriptPath'];

        // 驗證檔案的指定型態
        $this->check->fileType($param['filename'], $param['arraykey'], "image");

        $site   = $this->format->mixPathAndFilename($param['newname'], $param['site']);
        $neww   = $param['resize_width'];
        $newh   = $param['resize_height'];
        $retype = $param['resize_type'];
        $req    = $param['resize_quality'];

        $result = ImageResize($site, $site, $neww, $newh, $retype, $req); //成功返回存放路徑 失敗返回false

        if (!$result)
        {
            return false;
        }

        return true;
    }
}
