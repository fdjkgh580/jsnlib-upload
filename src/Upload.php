<?
namespace Jsnlib;

class Upload 
{
	
	public 	$filename;					//input的name屬性陣列名稱				ex. input="upl[]"	的 upl		
	public	$arraykey;					//input的name屬性鍵值				ex.	input="upl[0]"	的 0
	public	$pathaccess;				//路徑中或資料夾的權限				ex.0755 或最高0777

	//黑白名單擇一使用, 通常使用白名單自訂允許的檔案會比較安全
	public	$blacklist;					//黑名單的副檔明,	用逗號分開
	public 	$could_secondname; 			//允許的副檔名,		用逗號分開
	
	public	$size;						//指定大小 
	public	$site;						//上傳路徑(相對)
	public	$newname;					//新檔名
	
	
	//套件ImageResize 調整圖片大小相關設定
	public	$resizeImageScriptPath;		//套件ImageResize 路徑
	public	$resize_width;				//圖片重新調整寬
	public	$resize_height;				//圖片重新調整高
	public	$resize_type;				//套件ImageResize 縮放的類型(預設1) 不然有一邊會超過大小, 特殊使用 or 根據最大長度來判斷(可保證在指定大小內))
	public	$resize_quality;			//壓縮品質 (預設100)
	
	function __construct()
	{
		$this->arraykey			=	0;
		$this->pathaccess		=	"0777";
		$this->resize_width		=	"1000";
		$this->resize_height	=	"1000";
		$this->resize_type		=	1;
		$this->resize_quality	=	100;
	}
	
	//錯誤代碼
	function error_val()
	{
		$filename	=	$this->filename;
		$arykey		=	$this->arraykey;
		return $_FILES[$filename]['error'][$arykey];
	}
		
	//上傳檔案的副檔名	$Lower=1全部轉成小寫
	function scandN($lower=0)
	{
		$arykey		=	$this->arraykey;
		$name		=	$this->filename;
		$name		=	$_FILES[$name]['name'][$arykey];
		$scandN		=	strrchr($name, ".");					//最後出現的後方字串
		$scandN		=	ltrim($scandN,".");
		if ($lower == "1") $scandN = strtolower($scandN);		//小寫
		return $scandN;
	}
		
	//檢查副檔名的黑名單	
	function chk_blacklist()
	{

		//分解字串放入陣列
		$str		=	$this->blacklist;
		if (!empty($str)) {
			$ary		=	explode(",", $str);
			
			//比對上傳的副檔名
			$scandN		=	$this->scandN(1);
			
			foreach ($ary as $chkval) {
				if ($scandN	==	$chkval) {
					return 0;
					break;
					}
				}			
			}		
		return 1;
	}	
		
		
	//檢查允許的副檔名
	function chk_whitelist()
	{
		
		//分解字串放入陣列
		$str		=	$this->could_secondname;
		$ary		=	explode(",", $str);
		
		//比對上傳的副檔名
		$scandN		=	$this->scandN(1);
		foreach ($ary as $chkval) {
			if ($scandN	==	$chkval){
				return 1;
				break;
				}
			}			
		return 0;
	}	
	
	//檢查大小
	function chk_size()
	{
		$name		=	$this->filename;
		$arykey		=	$this->arraykey;
		$setsize	=	$this->size * 1000 * 1000; //MB
		$filesize	=	$_FILES[$name]['size'][$arykey];
		if ($filesize > $setsize) return 0;
		return 1;
	}
	
	//驗證檔案的指定型態
	function chk_type($needtype)
	{
		$name	=	$this->filename;
		$arykey	=	$this->arraykey;
		$type	=	$_FILES[$name]['type'][$arykey];
		$type	=	strtok($type,"/"); 			//分割字串
		
		if ($type != $needtype) return 0;
		return 1;
	}	

	function create_folder($site)
	{
		$ary        =	explode("/",$site);
		$filter_ary = array_filter($ary);
		$prev_folder = null;
		if (is_array($filter_ary)) foreach ($filter_ary as $key => $folder)
		{
			$create_folder = empty($prev_folder) ? $folder : $prev_folder . "/{$folder}";
			$prev_folder = $create_folder;

			$real    = realpath($create_folder);
			$isexist = file_exists($real);
			if ($isexist) continue;
			
			$mkresult = mkdir($create_folder, $this->pathaccess);
			if ($mkresult === false) throw new \Exception("嘗試建立路徑失敗：" . $folder);
		}
	}


	//準備好路徑
	function setuplpath()
	{

		//指定路徑若不存在，就分解並依序往下檢查、建立路徑(資料夾)
		if ( !file_exists($this->site))
		{
			$this->create_folder($this->site);
		}		

		//取得真實路徑
		$realpath = realpath($this->site);
		
		//
		if (!is_writable($realpath)) 
		{
			$perms = fileperms($realpath); //權限值10進位
			$perms = decoct ($perms); //10進未轉8進位
			$perms = substr($perms, -4); //取得後方4位的權值
			
			throw new \Exception("不可寫入：{$this->site}，權值是：{$perms}");
		}
		
		return 1;
	}
	
	//讓字串結尾保持 「 / 」 
	function auto_endslash($string)
	{

		$endstr		=	substr($string, -1);
		$token		=	"/";
		if ($endstr == $token) {
			unset($token);
			return $string;
		}
		return $string.$token;
	}
		
	
	//遇到未指定上傳檔案的就換下一個<input> 
	function isnextkey($key)
	{
		if (!empty($key)) return "0";
		$this->arraykey += 1; //
		return "1";
	}
		
	
	//驗證所有問題
	function chk_all()
	{
		$filename			=	$this->filename;
		$arykey				=	$this->arraykey;
		$original_file		=	$_FILES[$filename]['name'][$arykey]; //原始檔名+附檔名

		//1.檢查錯誤代碼
		$error	=	$this->error_val();
		
		if ($error	!=	0)	
		{
			switch ($error) 
			{
				case 4:
					throw new \Exception("請選擇檔案");				
					break; 
			}
			throw new \Exception("上傳錯誤，代碼:{$error}");
		}
		
		//2.檢查附檔名黑、白名單
		$bla			=	$this->blacklist;
		$whi			=	$this->could_secondname;
		$blacklist		=	$this->chk_blacklist();
		$whitelist		=	$this->chk_whitelist();
		
		
		
		if (!empty($bla) and !empty($whi)) 	throw new \Exception("黑、白名單請擇一設置。");

		elseif (!empty($bla)) 
		{
			if ($blacklist	==	0) throw new \Exception("不允許的檔案型態 : {$original_file}");
		}
			
		elseif (!empty($whi)) 
		{ 
			if ($whitelist	==	0) throw new \Exception("不允許的檔案型態 : {$original_file}");
		}
		
		//3.檔案大小
		$filesize	=	$_FILES[$filename]['size'][$arykey] / 1000 / 1000;	//上傳大小
		$setsize	=	$this->size;									//指定大小
		$size		=	$this->chk_size();
		if ($size == 0) throw new \Exception("您的『{$original_file}』檔案大小： {$filesize} MB；超過指定大小 : {$setsize} MB");
		
		//4.準備好存放路徑
		$this->setuplpath();
		
		//100.檢驗完成
		return 1;
	}
	
	//組合路徑與檔案完整名稱的字串
	function mix_path_file()
	{
		//指定位置
		$newname	=	$this->newname;
		//檢查並自動幫site結尾補上/ 
		$this->site	=	$this->auto_endslash($this->site);
		return $this->site . $newname;
	}
	
	//開始上傳 
	function start_upload()
	{
		//來源
		$name		=	$this->filename;
		$arykey		=	$this->arraykey;		
		$filetemp	=	$_FILES[$name]['tmp_name'][$arykey];
		
		//上傳完整路徑
		$site		=	$this->mix_path_file();
		
		if (copy($filetemp,$site)) return 1;

		throw new \Exception("上傳錯誤: copy({$filetemp}, {$site})也有可能是上傳的路徑沒有權限寫入。");		//使用copy事後須刪除
	}
		
	//結束上傳	
	function end_upload()
	{
		//清空暫存檔
		$name		=	$this->filename;
		$arykey		=	$this->arraykey;
		$filetemp	=	$_FILES[$name]['tmp_name'][$arykey];
		unlink($filetemp);
		return 1;
	}	
		
	//重新變更圖片大小
	function resizeImage($ImageResizeScriptPath)
	{
		include_once($ImageResizeScriptPath);
		
		$chktype	=	$this->chk_type("image");
		if ($chktype == 0) throw new \Exception("檔案非圖片格式類型，不可調整大小");
		
		$site	=	$this->mix_path_file();
		$neww	=	$this->resize_width;
		$newh	=	$this->resize_height;
		$retype	=	$this->resize_type;
		$req	=	$this->resize_quality;
		
		$result	=	ImageResize($site,$site,$neww,$newh,$retype,$req);	//成功返回存放路徑 失敗返回false
		if (!$result)	return 0;
		return 1;
	}

	/**
	 * 依照尺寸自動上傳，並自動命名
	 * @param   $param['prefix']                選)前贅字，若不指定將自動編排 4 字
	 * @param   $param['url']                   選)可回傳網址
	 * @param   $param['sizelist']['size']      選)放在後贅字，作為辨識尺寸
	 * @param   $param['sizelist']['width']     選)寬度
	 * @param   $param['sizelist']['height']    選)高度
	 */
	function fileupload($param)
	{
		$rand      = new \Jsnlib\Rand;
		$sizelist  = $param['sizelist'];
		$prefix    = isset($param['prefix']) ? $param['prefix'] : $rand->get(4, "2");
		$returnbox = [];

		//$val為原始上傳的文件名稱，若要將檔名使用原始檔名，建議配合uniqid() 
		foreach ($_FILES[$this->filename]["name"] as $fkey => $val) 
		{
			if ($this->isnextkey($val)) continue; //不限數量 (遇到未指定的就換下一個<input>)

			$N = $prefix . "_" . $rand->get(4, "2") . "_" . time();

			foreach ($sizelist as $key => $info)
			{
				$endupload = (!isset($sizelist[$key + 1])) ? "clean" : "retain";

				$newname = $N . "_" . $info['size'] . "." . $this->scandN(1);
				$this->resize_width  = $info['width'];
				$this->resize_height = $info['height'];
				$this->fileupload_multi($newname, $this->arraykey, 1, $endupload);

				// 回傳格式
				$back             = [];
				$back['filename'] = $newname;
				$back['path']     = $this->site . $newname;
				
				// 若指定網址
				if (isset($param['url']))
				{
					$back['url'] = trim($param['url'], "\ /") . "/" . $this->site . $newname;
				}

				$returnbox[$fkey][$info['size']] = $back;
			}
		}

		return $returnbox;
	}
	
		
	//傳遞參數陣列
	// $newname : 檔名
	// $add_arraykey : input name 陣列起鍵值, 通常是從頭開始所以填0
	// $resizeImg : 是否啟用縮圖
	// $endupload : 對於暫存檔的使用。clean為清空、retain為保留
	function fileupload_multi($newname, $add_arraykey, $resizeImg = 0, $endupload)
	{
		
		$this->newname 				= 	$newname;					//建議：新檔名(時間+鍵值+副檔名)

		//驗證無誤?
		$prepare	=	$this->chk_all();
		if ($prepare != 1) return 0;
		
		$this->start_upload();										//開始上傳
		
		//調整圖片大小(已寫自動判定格式)
		if ($resizeImg == 1) 
		{
			$Scpath		=	$this->resizeImageScriptPath;				//套件路徑
			$resultRE	=	$this->resizeImage($Scpath);
			if ($resultRE != 1) echo ("調整錯誤，圖片仍保持原始大小"); 
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
			$success	=	$this->end_upload();						//清空暫存
			$this->arraykey = $add_arraykey +1;							//接著準備上傳下一個<input>吧
			return $success;
		}
	}		
}		

