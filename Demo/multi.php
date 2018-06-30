<?php

header('Content-Type: text/html; charset=UTF-8');
require_once '../vendor/autoload.php';
$U = new \Jsnlib\Upload();

/**
 * 手動上傳，並可自行指定對應命名
 */
try
{
    if (isset($_POST['go']))
    {
        $inputname                = "upl"; //設定input file 的名稱, upl代表了 name="upl[]"
        $U->filename              = $inputname; //input name屬性的陣列名稱
        $U->arraykey              = 0; //input name陣列鍵值(起始值)
        $U->could_secondname      = "jpg"; //允許副檔名
        $U->pathaccess            = "0777"; //路徑權限
        $U->size                  = 50; //MB
        $U->site                  = "images"; //上傳路徑，結尾口有可無 /
        $U->resizeImageScriptPath = "../plugin/ImageResize.php"; //套件ImageResize 路徑    (可相對於class jsnupload 的位置)
        $U->resize_width          = 400; //若要不同的size就在下方each的時候再填寫即可
        $U->resize_height         = 400;
        $U->resize_quality        = 100; //JPG壓縮品質

        //$val為原始上傳的文件名稱，若要將檔名使用原始檔名，建議配合uniqid()

        foreach ($_FILES[$inputname]["name"] as $val)
        {
            if ($U->isNextKey($val))
            {
                continue;
            }
            //不限數量 (遇到未指定的就換下一個<input>)

            //開始上傳
            //小
            $newname_s        = uniqid(date("YmdHis_")) . "_s." . $U->filenameExtension(1);
            $U->resize_width  = 150;
            $U->resize_height = 150;
            $U->fileuploadMulti($newname_s, $U->arraykey, 1, "retain");

            //中
            $newname_m        = uniqid(date("YmdHis_")) . "_m." . $U->filenameExtension(1);
            $U->resize_width  = 400;
            $U->resize_height = 400;
            $U->fileuploadMulti($newname_m, $U->arraykey, 1, "retain");

            //大
            $newname_b        = uniqid(date("YmdHis_")) . "_b." . $U->filenameExtension(1);
            $U->resize_width  = 1280;
            $U->resize_height = 1280;
            $U->fileuploadMulti($newname_b, $U->arraykey, 1, "clean");

            ?>
            小: <a href="<?=$U->site . $newname_s;?>"><?=$U->site . $newname_s;?></a><br>
            中: <a href="<?=$U->site . $newname_m;?>"><?=$U->site . $newname_m;?></a><br>
            大: <a href="<?=$U->site . $newname_b;?>"><?=$U->site . $newname_b;?></a><br>
            <?php
}

        die;
    }
}
catch (Exception $e)
{
    echo $e->getMessage();
    die;
}

?>
<form method="post" enctype="multipart/form-data" action="">
    <div>訣竅：無論單比或多筆都使用name="upl[]"</div>
    <div><input name="upl[]" type="file" multiple></div>
    <div><input name="upl[]" type="file" multiple></div>
    <div><input name="go" type="submit" value="送出"></div>
</form>
