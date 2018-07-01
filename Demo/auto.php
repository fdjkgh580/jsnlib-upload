<?php

header('Content-Type: text/html; charset=UTF-8');
require_once '../vendor/autoload.php';
$U = new \Jsnlib\Upload();

/**
 * 自動上傳，系統自動命名
 */
try
{
    if (isset($_POST['go']))
    {
        $inputname                = "upl";                       //設定input file 的名稱, upl代表了 name="upl[]"
        $U->filename              = $inputname;                  //input name屬性的陣列名稱
        $U->arraykey              = 0;                           //input name陣列鍵值(起始值)
        $U->allow_type            = "jpg,sql";                       //允許副檔名
        $U->pathaccess            = "0777";                      //路徑權限
        $U->size                  = 5;                           //MB
        $U->site                  = "images";                    //上傳路徑，結尾口有可無 /
        $U->resizeImageScriptPath = "../plugin/ImageResize.php"; //套件ImageResize 路徑    (可相對於class jsnupload 的位置)
        $U->resize_width          = 400;                         //若要不同的size就在下方each的時候再填寫即可
        $U->resize_height         = 400;
        $U->resize_quality        = 100; //JPG壓縮品質

        $result = $U->fileupload(
            [
                'prefix' => 'MY',
                'url'    => 'http://localhost/edit_my_jsnlib_system/jsnlib-upload/Demo/',
                // 'sizelist' =>
                // [
                //     [
                //         'size'   => "S",
                //         'width'  => 150,
                //         'height' => 150,
                //     ],
                //     [
                //         'size'   => "M",
                //         'width'  => 800,
                //         'height' => 800,
                //     ],
                // ],

            ]);

        print_r($result);
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
    <div><input name="go" type="submit" value="送出"></div>
</form>
