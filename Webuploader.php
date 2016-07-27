<?php
/**
 * author: yidashi
 * Date: 2015/12/9
 * Time: 10:11
 */

namespace yidashi\webuploader;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\InputWidget;
use services\models\UpFileData;

class Webuploader extends InputWidget{
    //默认配置
    protected $_options;
    public $server;
    public function init()
    {
        parent::init();
        $this->options['boxId'] = isset($this->options['boxId']) ? $this->options['boxId'] : 'picker';
        $this->options['innerHTML'] = isset($this->options['innerHTML']) ? $this->options['innerHTML'] :'<button class="btn btn-primary">选择文件</button>';
        $this->options['previewWidth'] = isset($this->options['previewWidth']) ? $this->options['previewWidth'] : '250';
        $this->options['previewHeight'] = isset($this->options['previewHeight']) ? $this->options['previewHeight'] : '150';
    }
    public function run()
    {
        $this->registerClientJs();
        $value = Html::getAttributeValue($this->model, $this->attribute);
        $img_url = UpFileData::showImage($value);
        $content = $value ?
            Html::img(
            strpos($img_url, 'http:') === false ? (\Yii::getAlias('@static') . '/' . $img_url) : $img_url,
            ['width'=>$this->options['previewWidth'],'height'=>$this->options['previewHeight']]
            ) :
        $this->options['innerHTML'];
        
        //$content = $this->model[$this->attribute] ? Html::img(\Yii::getAlias('@web') . '/' . $this->model[$this->attribute], ['width'=>$this->options['previewWidth'],'height'=>$this->options['previewHeight']]) : '选择文件';
        if($this->hasModel()){
            return Html::tag('div', $content, ['id'=>$this->options['boxId']]) . Html::activeHiddenInput($this->model, $this->attribute);
        }else{
            return Html::tag('div', $content, ['id'=>$this->options['boxId']]) . Html::hiddenInput($this->name, $this->value);
        }
    }

    /**
     * 注册js
     */
    private function registerClientJs()
    {
        WebuploaderAsset::register($this->view);
        $web = \Yii::getAlias('@static');
        
        $maxSize = \Yii::$app->params['imageMaxSize'];
        
        $server = $this->server ?: Url::to(['webupload']);
        $swfPath = str_replace('\\', '/', \Yii::getAlias('@common/widgets/swf'));
        $this->view->registerJs(<<<JS
        var uploader = WebUploader.create({
        auto: true,
        fileVal: 'upfile',
        // swf文件路径
        swf: '{$swfPath}/Uploader.swf',

        // 文件接收服务端。gy
        server: '{$server}',

        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick: {
            id:'#{$this->options['boxId']}',
            //innerHTML:'{$this->options['innerHTML']}'
            multiple:false,
        },
        compress:false,//配置压缩的图片的选项。如果此选项为false, 则图片在上传前不进行压缩。
        chunked:true,// [默认值：false] 是否要分片处理大文件上传。
        chunkSize:3072000,//[默认值：5242880] 如果要分片，分多大一片？ 默认大小为5M.
        fileSingleSizeLimit:{$maxSize},//验证单个文件大小是否超出限制, 超出则不允许加入队列。
        accept: {
            title: 'Images',
            extensions: 'gif,jpg,jpeg,bmp,png',
            mimeTypes: 'image/*'
        }
        // 不压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
        //resize: false
    });
    uploader.onError = function( code ) {
        if(code == 'F_EXCEED_SIZE') {
           alert( '上传文件过大，请重新选择');
           return false;
        }

    };
    uploader.on( 'uploadProgress', function( file, percentage ) {
        var li = $( '#'+file.id ),
            percent = li.find('.progress .progress-bar');
    
        // 避免重复创建
        if ( !percent.length ) {
            percent = $('<div class="progress progress-striped active">' +
              '<div class="progress-bar" role="progressbar" style="width: 0%">' +
              '</div>' +
            '</div>').appendTo( li ).find('.progress-bar');
        }
    
        li.find('p.state').text('上传中 '+ (percentage * 100).toFixed(1) + '%');
    
        percent.css( 'width', percentage * 100 + '%' );
   });
    // 完成上传完了，成功或者失败，先删除进度条。
    uploader.on( 'uploadSuccess', function( file, data ) {
       if(data.flag) {
            $( '#'+file.id ).find('p.state').text('上传成功').fadeOut();
            $( '#{$this->options['boxId']} .webuploader-pick' ).html('<img src="{$web}'+data.url+'" width="{$this->options['previewWidth']}" height="{$this->options['previewHeight']}"/>');
            $( '#{$this->options['id']}' ).val( data.id);
            $( '#{$this->options['boxId']} .webuploader-pick' ).siblings('div').width("{$this->options['previewWidth']}").height("{$this->options['previewHeight']}");
       } else {
            alert(data.state);
            return false;
       }
    });
    uploader.on('error',function(type){
       if(type == 'Q_TYPE_DENIED') {
          alert('上传图片支持jpg、jpeg，gif，png，bmp格式');
          return false;
       }
    });
        
    
JS
        );
    }
} 