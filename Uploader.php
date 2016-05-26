<?php
namespace yidashi\webuploader;

class Uploader
{
    private $fileField; //文件域名
    private $file; //文件上传对象
    private $base64; //文件上传对象
    private $config; //配置信息
    private $oriName; //原始文件名
    private $fileName; //新文件名
    private $fullName; //完整文件名,即从当前配置目录开始的URL
    private $filePath; //完整文件名,即从当前配置目录开始的URL
    private $fileSize; //文件大小
    private $fileType; //文件类型
    private $stateInfo; //上传状态信息,
    private $flag = false;
    private $stateMap = array( //上传状态映射表，国际化用户需考虑此处数据的国际化
        "SUCCESS", //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制",
        "文件大小超出 MAX_FILE_SIZE 限制",
        "文件未被完整上传",
        "没有文件被上传",
        "上传文件为空",
        "ERROR_TMP_FILE"           => "临时文件错误",
        "ERROR_TMP_FILE_NOT_FOUND" => "找不到临时文件",
        "ERROR_SIZE_EXCEED"        => "文件大小超出网站限制",
        "ERROR_TYPE_NOT_ALLOWED"   => "文件类型不允许",
        "ERROR_CREATE_DIR"         => "目录创建失败",
        "ERROR_DIR_NOT_WRITEABLE"  => "目录没有写权限",
        "ERROR_FILE_MOVE"          => "文件保存时出错",
        "ERROR_FILE_NOT_FOUND"     => "找不到上传文件",
        "ERROR_WRITE_CONTENT"      => "写入文件内容错误",
        "ERROR_UNKNOWN"            => "未知错误",
        "ERROR_DEAD_LINK"          => "链接不可用",
        "ERROR_HTTP_LINK"          => "链接不是http链接",
        "ERROR_HTTP_CONTENTTYPE"   => "链接contentType不正确"
    );

    /**
     * 构造函数
     * @param $fileField  表单名称
     * @param $config 配置项
     * @param string $type 是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
     */
    public function __construct($fileField, $config, $type = "upload")
    {
        $this->fileField = $fileField;
        $this->config = $config;
        $this->type = $type;
        if ($type == "remote") {
            $this->saveRemote();
        } else if ($type == "base64") {
            $this->upBase64();
        } else {
            $this->upFile();
        }
//        $this->stateMap['ERROR_TYPE_NOT_ALLOWED'] = iconv('unicode', 'utf-8', $this->stateMap['ERROR_TYPE_NOT_ALLOWED']);
    }

    /**
     * 上传文件的主处理方法
     * @return mixed
     */
    private function upFile()
    {
        if(empty($_FILES[$this->fileField]['name'])) {
           $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }
        
        $file = $this->file = $_FILES[$this->fileField];
        if (!$file) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }
        if ($this->file['error']) {
            $this->stateInfo = $this->getStateInfo($file['error']);
            return;
        } else if (!file_exists($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE_NOT_FOUND");
            return;
        } else if (!is_uploaded_file($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMPFILE");
            return;
        }

        $this->oriName = $file['name'];
        $this->fileSize = $file['size'];
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }
        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }
        //移动文件
        if (!(move_uploaded_file($file["tmp_name"], $this->filePath) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_MOVE");
        } else { //移动成功
            $this->stateInfo = $this->stateMap[0];
            $this->flag = true;
        }
    }

    /**
     * 处理base64编码的图片上传
     * @return mixed
     */
    private function upBase64()
    {
        $base64Data = $_POST[$this->fileField];
        
        if(empty($base64Data)) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }
        
        $img = base64_decode($base64Data);
        
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Data, $result)) {
            $fileType = $result[2];
        }
        
        $this->oriName = $this->config['oriName'];
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }
        //检查是否不允许的文件格式
        if (!in_array(".".$fileType, $this->config["allowFiles"])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }
        
        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }

        $data = str_replace($result[1], '', $base64Data);
        //移动文件
        if (!(file_put_contents($this->filePath,base64_decode($data)) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        } else { //移动成功
            $this->stateInfo = $this->stateMap[0];
            $this->flag = true;
        }

    }

    /**
     * 拉取远程图片
     * @return mixed
     */
    private function saveRemote()
    {
        $imgUrl = htmlspecialchars($this->fileField);
        $imgUrl = str_replace("&amp;", "&", $imgUrl);

        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_LINK");
            return;
        }
        //获取请求头并检测死链
        $heads = get_headers($imgUrl);
        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            $this->stateInfo = $this->getStateInfo("ERROR_DEAD_LINK");
            return;
        }
        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl, '.'));
        if (!in_array($fileType, $this->config['allowFiles']) || stristr($heads['Content-Type'], "image")) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_CONTENTTYPE");
            return;
        }

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

        $this->oriName = $m ? $m[1] : "";
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }

        //移动文件
        if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        } else { //移动成功
            $this->stateInfo = $this->stateMap[0];
            $this->flag = true;
        }

    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function getStateInfo($errCode)
    {
        return !$this->stateMap[$errCode] ? $this->stateMap["ERROR_UNKNOWN"] : $this->stateMap[$errCode];
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function getFileExt()
    {
        return strtolower(strrchr($this->oriName, '.'));
    }

    /**
     * 重命名文件
     * @return string
     */
    private function getFullName()
    {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->config["pathFormat"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);

        //过滤文件名的非法自负,并替换文件名
        $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
        $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        $format = str_replace("{filename}", $oriName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }

        $ext = $this->getFileExt();
        return $format . $ext;
    }

    /**
     * 获取文件名
     * @return string
     */
    private function getFileName()
    {
        return substr($this->filePath, strrpos($this->filePath, '/') + 1);
    }

    /**
     * 获取文件完整路径
     * @return string
     */
    private function getFilePath()
    {
        $fullname = $this->fullName;
//        $rootPath = $_SERVER['DOCUMENT_ROOT'];
        $rootPath = \Yii::getAlias('@staticroot');
        if (substr($fullname, 0, 1) != '/') {
            $fullname = '/' . $fullname;
        }

        return $rootPath . $fullname;
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function checkType()
    {
        return in_array($this->getFileExt(), $this->config["allowFiles"]);
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function  checkSize()
    {
        return $this->fileSize <= ($this->config["maxSize"]);
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        return array(
            'flag'=>$this->flag,
            "state"    => $this->stateInfo,
            "url"      => $this->fullName,
            "title"    => $this->fileName,
            "original" => $this->oriName,
            "type"     => $this->fileType,
            "size"     => $this->fileSize
        );
    }
    
    /**
     * 生成缩略图
     * @param string $width 宽度
     * @param string $height 高度
     * @param int $type 1指定大小 2等比例缩放
     */
    public function upFileThumb($width=120,$height=120,$type=1){
        
            /*加载图片*/
            if(!$this->filePath) {
                $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
                return;
            }
        
            $thumb_parth  = $this->filePath;//大图全路径
            
            $file_name = str_replace('image','thumb',$thumb_parth);//缩略图全路径
            
            $thumb_dir = dirname($file_name);//缩略图不带文件名称路径
           
            //创建目录失败
            if (!file_exists($thumb_dir) && !mkdir($thumb_dir, 0777, true)) {
                $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
                return;
            } else if (!is_writeable($thumb_dir)) {
                $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
                return;
            }
            
            if($type == 1) { //指定尺寸裁剪
                return $this->thumbSpecifiedDimensions($width, $height, $file_name);
            }else {
                //等比例尺寸
                return $this->thumbEqualProportion($width, $height, $file_name);
            }
           
    }
    
    /**
     * 按照给定的尺寸等比例缩放
     * @param string $width 宽度
     * @param string $height 高度
     * @param string $file_name 文件名
     */
    private  function thumbEqualProportion($width,$height,$file_name){
        
        list($srcWidth, $srcHeight,$srcType) = getimagesize($this->filePath);
        
        switch ($srcType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($this->filePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($this->filePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($this->filePath);
                break;
            default:
                $this->stateInfo = $this->getStateInfo("Type error");
                return;
        }
        
        $thumbImage = imagecreatetruecolor($srcWidth, $srcHeight);
        
        $bg = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
        imagefill($thumbImage, 0, 0, $bg);
        imagecolortransparent($thumbImage, $bg);
        	
        $ratio_w = 1.0 * $width / $srcWidth;
        $ratio_h = 1.0 * $height / $srcHeight;
        $ratio = 1.0;
        
        if ($ratio_w > 1 && $ratio_h > 1) {
            $thumbImage = imagecreatetruecolor($srcWidth, $srcHeight);
            imagecopy($thumbImage, $image, 0, 0, 0, 0, $srcWidth, $srcHeight);
        } else {
            $ratio = $ratio_w > $ratio_h ? $ratio_h : $ratio_w;
            $tmp_w = (int) ($srcWidth * $ratio);
            $tmp_h = (int) ($srcHeight * $ratio);
            $thumbImage = imagecreatetruecolor($tmp_w, $tmp_h);
            imagecopyresampled($thumbImage, $image, 0, 0, 0, 0, $tmp_w, $tmp_h, $srcWidth, $srcHeight);
        }
        
        switch ($srcType) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbImage, $file_name , 75);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbImage,$file_name , 75);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbImage, $file_name, 6);
                break;
        }
        
        imagedestroy($thumbImage);
        imagedestroy($image);
        
        return true;
    }
    
    /**
     * 按照指定的尺寸进行裁剪，
     * @param string $width 宽度
     * @param string $height 高度
     * @param string $file_name 文件名
     */
    private  function thumbSpecifiedDimensions($width,$height,$file_name){
        
        list($srcWidth, $srcHeight,$srcType) = getimagesize($this->filePath);
        
        $suo_img=imagecreatetruecolor($width,$height);
        
        switch ($srcType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($this->filePath); //jpeg file
                imagejpeg($suo_img,$file_name);
                imagecopyresampled($suo_img,$image,0,0,0,0,$width,$height,$srcWidth,$srcHeight);
                imagejpeg($suo_img,$file_name);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($this->filePath); //gif file
                imagegif($suo_img,$file_name);
                imagecopyresampled($suo_img,$image,0,0,0,0,$width,$height,$srcWidth,$srcHeight);
                imagegif($suo_img,$file_name);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($this->filePath); //png file
                imagepng($suo_img,$file_name);
                imagecopyresampled($suo_img,$image,0,0,0,0,$width,$height,$srcWidth,$srcHeight);
                imagepng($suo_img,$file_name);
                break;
            default:
               break;
        }
        
        imagedestroy($suo_img);
        return true;
    }
}