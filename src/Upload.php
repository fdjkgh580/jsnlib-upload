<?php
namespace Jsnlib;

class Upload
{
    public $filename;   //input的name屬性陣列名稱 ex. input="upl[]"   的 upl
    public $arraykey;   //input的name屬性鍵值 ex. input="upl[0]"  的 0
    public $pathaccess; //路徑中或資料夾的權限 ex.0755 或最高0777

                        //黑白名單擇一使用, 通常使用白名單自訂允許的檔案會比較安全
    public $blacklist;  //黑名單的副檔明, 用逗號分開
    public $allow_type; //允許的副檔名, 用逗號分開

    public $size;    //指定大小
    public $site;    //上傳路徑(相對)
    public $newname; //新檔名

                                   //套件ImageResize 調整圖片大小相關設定
    public $resizeImageScriptPath; //套件ImageResize 路徑
    public $resize_width;          //圖片重新調整寬
    public $resize_height;         //圖片重新調整高
    public $resize_type;           //套件ImageResize 縮放的類型(預設1) 不然有一邊會超過大小, 特殊使用 or 根據最大長度來判斷(可保證在指定大小內))
    public $resize_quality;        //壓縮品質 (預設100)

    protected $check;
    protected $format;

    public function __construct()
    {
        $this->arraykey       = 0;
        $this->pathaccess     = "0777";
        $this->resize_width   = "1000";
        $this->resize_height  = "1000";
        $this->resize_type    = 1;
        $this->resize_quality = 100;
        $this->check          = new \Jsnlib\Upload\Check();
        $this->format         = new \Jsnlib\Upload\Format();
    }

    //遇到未指定上傳檔案的就換下一個<input>
    public function isNextKey($key)
    {
        if (!empty($key))
        {
            return false;
        }

        $this->arraykey += 1;

        return true;
    }

    //開始上傳
    private function uploadStart()
    {
        //來源
        $name     = $this->filename;
        $arykey   = $this->arraykey;
        $filetemp = $_FILES[$name]['tmp_name'][$arykey];

        //上傳完整路徑
        $site = $this->format->mixPathAndFilename($this->newname, $this->site);

        if (!file_exists($filetemp))
        {
            throw new \Exception("系統錯誤，找不到上傳的暫存檔。");
        }

        if (copy($filetemp, $site))
        {
            return 1;
        }

        throw new \Exception("上傳錯誤: copy({$filetemp}, {$site})也有可能是上傳的路徑沒有權限寫入。"); //使用copy事後須刪除
    }

    //結束上傳
    private function uploadEnd()
    {
        //清空暫存檔
        $name     = $this->filename;
        $arykey   = $this->arraykey;
        $filetemp = $_FILES[$name]['tmp_name'][$arykey];

        unlink($filetemp);
        return 1;
    }

    //重新變更圖片大小
    private function resizeImage($ImageResizeScriptPath)
    {
        include_once $ImageResizeScriptPath;

        // 驗證檔案的指定型態
        $this->check->fileType($this->filename, $this->arraykey, "image");

        $site   = $this->format->mixPathAndFilename($this->newname, $this->site);
        $neww   = $this->resize_width;
        $newh   = $this->resize_height;
        $retype = $this->resize_type;
        $req    = $this->resize_quality;

        $result = ImageResize($site, $site, $neww, $newh, $retype, $req); //成功返回存放路徑 失敗返回false

        if (!$result)
        {
            return 0;
        }

        return 1;
    }

    /**
     * 依照尺寸自動上傳，並自動命名
     * @param $param['prefix']             選)前贅字，若不指定將自動編排 4 字
     * @param $param['url']                選)可回傳網址
     * @param $param['sizelist']['size']   選)放在後贅字，作為辨識尺寸
     * @param $param['sizelist']['width']  選)寬度
     * @param $param['sizelist']['height'] 選)高度
     */
    public function fileupload($param)
    {
        $rand      = new \Jsnlib\Rand();
        $sizelist  = isset($param['sizelist']) ? $param['sizelist'] : false;
        $prefix    = isset($param['prefix']) ? $param['prefix'] : $rand->get(4, "2");
        $returnbox = [];

        // $org_filename 為原始上傳的文件名稱，若要將檔名使用原始檔名，建議配合uniqid()
        foreach ($_FILES[$this->filename]["name"] as $fkey => $org_filename)
        {
            if ($this->isNextKey($org_filename))
            {
                continue;
            }

            //不限數量 (遇到未指定的就換下一個<input>)
            $N = $prefix . "_" . $rand->get(4, "2") . "_" . time();

            if ($sizelist != false)
            {
                foreach ($sizelist as $key => $info)
                {
                    $endupload = (!isset($sizelist[$key + 1])) ? "clean" : "retain";

                    $newname             = $N . "_" . $info['size'] . "." . $this->format->filenameExtension($this->filename, $this->arraykey);
                    $this->resize_width  = $info['width'];
                    $this->resize_height = $info['height'];
                    $this->fileuploadMulti($newname, $this->arraykey, 1, $endupload);

                    // 回傳格式
                    $back = $this->format->back(
                        $this->newname,
                        $this->format->mixPathAndFilename($this->newname, $this->site),
                        $param['url']
                    );

                    $returnbox[$fkey][$info['size']] = $back;
                }
            }
            else
            {
                $this->newname = $N . "." . $this->format->filenameExtension($this->filename, $this->arraykey);

                //驗證
                $this->check->all(
                    [
                        'filename'    => $this->filename,
                        'arykey'      => $this->arraykey,
                        'blacklist'   => $this->blacklist,
                        'allow_type'  => $this->allow_type,
                        'filenameExt' => $this->format->filenameExtension($this->filename, $this->arraykey),
                        'setSize'     => $this->size,
                        'site'        => $this->site,
                    ]);

                $tmp = $_FILES['upl']['tmp_name'][0];

                //開始上傳
                $this->uploadStart();

                //清空暫存
                $this->uploadEnd();

                // 回傳格式
                $back = $this->format->back(
                    $this->newname,
                    $this->format->mixPathAndFilename($this->newname, $this->site),
                    $param['url']
                );

                $returnbox[$fkey] = $back;
            }
        }

        return $returnbox;
    }

    /**
     * 傳遞參數陣列
     * @param string  $newname      檔名
     * @param integer $add_arraykey 陣列起鍵值, 通常是從頭開始所以填0
     * @param integer $resizeImg    是否啟用縮圖
     * @param string  $endupload    對於暫存檔的使用。clean為清空、retain為保留
     */
    public function fileuploadMulti($newname, $add_arraykey, $resizeImg = 0, $endupload)
    {
        $this->newname = $newname; //建議：新檔名(時間+鍵值+副檔名)

        //驗證
        $this->check->all(
            [
                'filename'    => $this->filename,
                'arykey'      => $this->arraykey,
                'blacklist'   => $this->blacklist,
                'allow_type'  => $this->allow_type,
                'filenameExt' => $this->format->filenameExtension($this->filename, $this->arraykey),
                'setSize'     => $this->size,
                'site'        => $this->site,
            ]);

        $this->uploadStart(); //開始上傳

        //調整圖片大小(已寫自動判定格式)
        if ($resizeImg == 1)
        {
            $Scpath   = $this->resizeImageScriptPath; //套件路徑
            $resultRE = $this->resizeImage($Scpath);

            if ($resultRE != 1)
            {
                echo ("調整錯誤，圖片仍保持原始大小");
            }
        }

        /*
         * [關於參數endupload]
         * endupload 可用參數:clean為清空、retain為保留
         * 說明：
         *
         *   若此次是最後一次上傳，請使用清空暫存檔($endupload = 1)
         *   若是上傳一個檔案，壓縮成多個不同比例的圖片
         *   若不是最後一個比例，就不要使用參數endupload ($endupload = 0)
         *   直到最後一個比例時才使用 ($endupload = 1)
         */

        if ($endupload == "clean")
        {
            $success        = $this->uploadEnd(); //清空暫存
            $this->arraykey = $add_arraykey + 1;  //接著準備上傳下一個<input>吧
            return $success;
        }
    }
}
