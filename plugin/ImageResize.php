<?php
/**
 The MIT License

 Copyright (c) 2007 <Tsung-Hao>

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.
 *
 * 抓取要縮圖的比例, 下述只處理 jpeg
 * $from_filename : 來源路徑, 檔名, ex: /tmp/xxx.jpg
 * $save_filename : 縮圖完要存的路徑, 檔名, ex: /tmp/ooo.jpg
 * $in_width : 縮圖預定寬度
 * $in_height: 縮圖預定高度
 * $ResizeType: 
 *				1 : (一般使用這個) 不然有一邊會超過大小, 特殊使用 or 根據最大長度來判斷(可保證在指定大小內)
 * 			2 : 根據最小長度來判斷整張圖縮放(只有一邊在指定大小內, 除非是正方形, 若Size在指定大小內無效)
 * 			3 : 根據最小長度來判斷整張圖縮放(只有一邊在指定大小內, 除非是正方形, 若Size在指定大小內則放大)
 * 			4 : 非等比縮圖
 * 			5 : 非等比縮圖 - 按照寬
 * $GetCenterImageOnly :
 *				true : 若是 true 則會重新取樣, 取出跟設定Size一樣大的縮圖, 並且只取得中央影像
 *				false: 若是 false 不會重新取樣, 不更改資料直接回傳
 * $quality  : 縮圖品質(1~100)
 *
 * Usage:
 *   ImageResize('ram/xxx.jpg', 'ram/ooo.jpg');
 */
ini_set("memory_limit", "512M") ;

function ImageResize($from_filename, $save_filename = null, $in_width=400, $in_height=300, $ResizeType = 1, $quality = 100)
{
    $allow_format = array('jpeg', 'png', 'gif');
	$sub_name = $t = '';

    // Get new dimensions
    $img_info = getimagesize($from_filename);
    $width    = $img_info['0'];
    $height   = $img_info['1'];
    $imgtype  = $img_info['2'];
    $imgtag   = $img_info['3'];
    $bits     = $img_info['bits'];
    $channels = $img_info['channels'];
    $mime     = $img_info['mime'];

	if ($save_filename == null || $save_filename == "") {
		$Pos1 = strrpos($from_filename,"\\") ;
		$Pos2 = strrpos($from_filename,"/") ;
		$OrignPath = "" ;
		if ($Pos1 || $Pos2) {
			if ($Pos1 > $Pos2)
				$OrignPath = substr($from_filename,0,$Pos1+1) ;
			else if ($Pos1 <= $Pos2)
				$OrignPath = substr($from_filename,0,$Pos2+1) ;
		}
		
		$UPLOAD_Extension = explode(".", $from_filename);
		$UPLOAD_Extension = strtolower($UPLOAD_Extension[count($UPLOAD_Extension)-1]) ; 
		mt_srand((double)microtime()*1000000);
		$save_filename = $OrignPath . "File_" . date("YmdHis") . str_pad(mt_rand(10,99),2,0,STR_PAD_LEFT) . "_Thumb." . $UPLOAD_Extension;
	}
			
   list($t, $sub_name) = explode('/', $mime);
    if ($sub_name == 'jpg') {
        $sub_name = 'jpeg';
    }

    if (!in_array($sub_name, $allow_format)) {
        return false;
    }

    
	if ($ResizeType == 4)
	{
		// 非等比縮圖
		$new_width  = $in_width;
		$new_height = $in_height;
	} else if ($ResizeType == 5) {
		$percent = getResizePercent2($width, $height, $in_width, $in_height, $ResizeType);
		$new_width  = $width * $percent;
		$new_height = $height * $percent;
	} else {
		// 取得縮在此範圍內的比例
		$percent = getResizePercent($width, $height, $in_width, $in_height, $ResizeType);
		$new_width  = $width * $percent;
		$new_height = $height * $percent;
	}

    // Resample
    $image_new = imagecreatetruecolor($new_width, $new_height);
	
	//使得背景可以保持透明---------------------------
	@imagealphablending( $image_new, false ); 
	@imagesavealpha( $image_new, true ); 
	//-------------------------------------------------

	if ($sub_name == "png") {
		$quality = (floor($quality/10) < 9 && floor($quality/10) > 0 ? floor($quality/10) : 8 ) ;
		$image = imagecreatefrompng($from_filename);
		imagecopyresampled($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		$FileResult = imagepng($image_new, $save_filename, $quality);
	} else if ($sub_name == "gif") {
		$image = imagecreatefromgif($from_filename);
		imagecopyresampled($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		$FileResult = imagegif($image_new, $save_filename, $quality);
	} else {
		$image = imagecreatefromjpeg($from_filename);
		imagecopyresampled($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		$FileResult = imagejpeg($image_new, $save_filename, $quality);
	}
	
	if ($FileResult) {
		return $save_filename ;
	} else {
		return false ;
	}
}

/**
 * 抓取要縮圖的比例
 * $source_w : 來源圖片寬度
 * $source_h : 來源圖片高度
 * $inside_w : 縮圖預定寬度
 * $inside_h : 縮圖預定高度
 *
 * Test:
 *   $v = (getResizePercent(1024, 768, 400, 300));
 *   echo 1024 * $v . "\n";
 *   echo  768 * $v . "\n";
 */
function getResizePercent($source_w, $source_h, $inside_w, $inside_h, $ResizeType = 1)
{
    if ($source_w < $inside_w && $source_h < $inside_h && $ResizeType != 3) {
        return 1; // Percent = 1, 如果都比預計縮圖的小就不用縮
    }

    $w_percent = $inside_w / $source_w;
    $h_percent = $inside_h / $source_h;

 	if ($ResizeType == 1)
	    return ($w_percent > $h_percent) ? $h_percent : $w_percent;
	else
	    return ($w_percent < $h_percent) ? $h_percent : $w_percent;
}

function getResizePercent2($source_w, $source_h, $inside_w, $inside_h, $ResizeType = 1)
{
	$source_w = $inside_w / $source_w ;
	
	return $source_w ;
}

?>