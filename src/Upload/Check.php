<?php
namespace Jsnlib\Upload;

class Check
{
    protected $upload;

    /**
     * 驗證檔案的指定型態
     * @param $filename
     * @param $arykey
     * @param $needType   要符合的型態
     */
    public function fileType($filename, $arykey, $needType)
    {
        $type = $_FILES[$filename]['type'][$arykey];
        $type = strtok($type, "/"); //分割字串

        if ($type !== $needType)
        {
            throw new \Exception("檔案非圖片格式類型，不可調整大小");
        }

        return true;
    }

    private function inList($orgFileName, $filenameExt, $blacklist, $allowlist)
    {
        // 在黑名單？
        $isInBlackList = $this->isInBlackList($filenameExt, $blacklist);

        // 在白名單？
        $isInAllowType = $this->isInAllowType($filenameExt, $allowlist);

        if (!empty($blacklist) and !empty($allowlist))
        {
            throw new \Exception("黑、白名單請擇一設置。");
        }
        // 若在黑名單中
        elseif (!empty($blacklist) and $isInBlackList === true)
        {
            throw new \Exception("不允許的檔案型態 : {$orgFileName}");
        }
        // 若不在白名單中
        elseif (!empty($allowlist) and $isInAllowType === false)
        {
            throw new \Exception("不允許的檔案型態 : {$orgFileName}");
        }
    }

    private function filesCode($filename, $arykey)
    {
        // 檢查錯誤代碼
        $error = $this->getFileErrorCode($filename, $arykey);

        if ($error != 0)
        {
            switch ($error)
            {
                case 4:
                    throw new \Exception("請選擇檔案");
                    break;
            }

            throw new \Exception("上傳錯誤，代碼:{$error}");
        }
    }

    private function size($filename, $arykey, $setSize, $orgFileName)
    {
        $filesize      = $_FILES[$filename]['size'][$arykey] / 1000 / 1000; //上傳大小
        $allowFileSize = $this->allowFileSize($filename, $arykey, $setSize);

        if ($allowFileSize === false)
        {
            throw new \Exception("您的 『{$orgFileName}』 檔案大小： {$filesize} MB；超過指定大小 : {$setSize} MB");
        }
    }

    /**
     * 驗證所有問題
     * @param $param['filename']
     * @param $param['arykey']
     * @param $param['blacklist']
     * @param $param['allowlist']
     * @param $param['filenameExt']
     * @param $param['setSize']
     * @param $param['site']
     */
    public function all($param)
    {
        // print_r($param);die;
        foreach ($param as $key => $val)
        {
            $$key = $val;
        }

        // 原始檔名 + 附檔名
        $orgFileName = $_FILES[$filename]['name'][$arykey];

        // 檢查錯誤代碼
        $this->filesCode($filename, $arykey);

        // 檢查允許或不允許的名單
        $this->inList($orgFileName, $filenameExt, $blacklist, $allowlist);

        //檔案大小
        $this->size($filename, $arykey, $setSize, $orgFileName);

        // 準備好存放路徑
        $this->setUplPath($site);

        return true;
    }

    //準備好路徑
    private function setUplPath($site)
    {
        //指定路徑若不存在，就分解並依序往下檢查、建立路徑(資料夾)
        if (!file_exists($site))
        {
            $this->createFolder($site);
        }

        //取得真實路徑
        $realpath = realpath($site);

        if (!is_writable($realpath))
        {
            $perms = fileperms($realpath); //權限值10進位
            $perms = decoct($perms);       //10進未轉8進位
            $perms = substr($perms, -4);   //取得後方4位的權值

            throw new \Exception("不可寫入：{$site}，權值是：{$perms}");
        }

        return true;
    }

    private function createFolder($site)
    {
        $ary         = explode("/", $site);
        $filter_ary  = array_filter($ary);
        $prev_folder = null;

        if (is_array($filter_ary))
        {
            foreach ($filter_ary as $key => $folder)
            {
                $create_folder = empty($prev_folder) ? $folder : $prev_folder . "/{$folder}";
                $prev_folder   = $create_folder;

                $real    = realpath($create_folder);
                $isexist = file_exists($real);

                if ($isexist)
                {
                    continue;
                }

                $mkresult = mkdir($create_folder, $this->pathaccess);

                if ($mkresult === false)
                {
                    throw new \Exception("嘗試建立路徑失敗：" . $folder);
                }
            }
        }
    }

    //取得 $_FILES 錯誤代碼
    private function getFileErrorCode($filename, $arykey)
    {
        return $_FILES[$filename]['error'][$arykey];
    }

    /**
     * 附檔名在黑名單嗎
     * @param string $blacklist
     */
    private function isInBlackList($filenameExt, $blacklist = null)
    {
        if (!empty($blacklist))
        {
            $ary = explode(",", $blacklist);

            //比對上傳的副檔名
            foreach ($ary as $chkval)
            {
                if ($filenameExt == $chkval)
                {
                    return true;
                    break;
                }
            }
        }

        return false;
    }

    /**
     * 檢查允許的白名單
     * @param string $filenameExt 比對上傳的副檔名
     * @param string $allowlist
     */
    private function isInAllowType($filenameExt, $allowlist)
    {
        //分解字串放入陣列
        $ary = explode(",", $allowlist);

        //比對上傳的副檔名
        foreach ($ary as $chkval)
        {
            if ($filenameExt == $chkval)
            {
                return true;
                break;
            }
        }

        return false;
    }

    /**
     * 檢查檔案大小
     * @param  string $filename
     * @param  int    $arykey
     * @param  int    $setSize    指定的檔案大小
     * @return bool
     */
    private function allowFileSize($filename, $arykey, $setSize)
    {
        $filesize  = $_FILES[$filename]['size'][$arykey];
        $setSizeMB = $setSize * 1000 * 1000; //MB

        return ($filesize > $setSizeMB) ? false : true;
    }
}
