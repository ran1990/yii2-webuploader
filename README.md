##webuploader

	===============================
![Effect picture 1](https://github.com/ran1990/yii2-webuploader/blob/master/multi_image.png "Effect picture 1")  
![Effect picture 1](https://github.com/ran1990/yii2-webuploader/blob/master/more_file.png "Effect picture 1")  
	
## 1.安装  
```
composer require life2016/yii2-webuploader

借鉴yiidashi，地址：https://github.com/yidashi/yii2-webuploader
	
本次改版主要是，支持多张图片上传，图片与文件分开，严格控制图片上传格式，
需要注意的是：文件中涉及到Yii::$app->params[变量]，请自行前往设置，这里不做说明，贴代码如下：
    // 允许图片上传最大大小字节
    'imageMaxSize' => 2048000, 
    //允许文件上传最大大小,字节
    'fileMaxSize' => 10240000,
    //允许视频上传最大大小字节
    'videoMaxSize' => 20480000,
    //多文件上传限制数量
    'MaxNumber' => 10,
	
	    //文件后缀限制
    'file_exts' => [
        "rar",
        "zip",
        "tar",
        "gz",
        "doc",
        "docx",
        "xls",
        "xlsx",
        "ppt",
        "pptx",
        "pdf",
        "txt",
        "md",
        "xml",
        "csv"
    ],
    //视频后缀限制
    'video_exts' => [
        "flv",
        "swf",
        "mkv",
        "avi",
        "rm",
        "rmvb",
        "mpeg",
        "mpg",
        "ogg",
        "ogv",
        "mov",
        "wmv",
        "mp4",
        "webm",
        "mp3",
        "wav",
        "mid"
    ],
    //图片后缀限制
    'image_exts' => [
        'gif',
        'jpg',
        'jpeg',
        'bmp',
        'png'
    ],
	
```

## 2.使用 

## 2.1使用FileWebUploader上传文件

```
<?=$form->field($model,$attributeName)->widget('yiidashi\webuploader\FileWebUploader',['options'=>['boxId'=>'picker','type'=>'image|file'],'server'=>'上传服务器地址'])?>
```

##2.2使用MultipeWebuploader上传多图片，严格限制图片格式，支持限制上传多少张图片，参数number,['options'=>['boxId' => 'picker','number'=>'10']]

```
<?= $form->field($model,'attributeName')->widget('yidashi\webuploader\MultipeWebuploader',['options'=>['boxId' => 'picker', 'previewWidth'=>200, 'previewHeight'=>150]]); ?>
```

## 2.3使用Webuploader上传单张图片，
```
<?= $form->field($model,'attributeName')->widget('yidashi\webuploader\Webuploader',['options'=>['boxId' => 'picker', 'previewWidth'=>200, 'previewHeight'=>150]]); ?>
```
注意：options非必填，同一页面多个上传按钮，需要特别指定['options'=>['boxId'=>'不同id','multiple'=>'第几个上传文件按钮序号']，multiple解决webuploader插件在dispaly隐藏情况下不事件失效，只需要隐藏后执行函数initUploadImage(第几个上传文件按钮序号)


使用默认action处理的话controller需要添加
```
public function actions()
{
    return [
        'webupload' => 'yidashi\webuploader\WebuploaderAction'
    ];
}
```  
如果需要使用自己的上传程序处理需添加server属性
```
<?= $form->field($model,'attributeName')->widget('yidashi\webuploader\Webuploader',['server'=>'你自己的处理路由']); ?>
```