<?php
/**上传多个文件
 * author: ran.ran
 * Date: 2015/12/9
 * Time: 10:11
 */

namespace yidashi\webuploader;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\InputWidget;
use services\models\UpFileData;
use Yii;
class FileWebUploader extends InputWidget{
    
    //默认配置
    protected $_options;
    public $server;
    public function init()
    {
        parent::init();
        $this->options['type'] = isset($this->options['type']) ? $this->options['type'] : 'file';
        $this->options['number'] = isset($this->options['number']) ? $this->options['number'] : Yii::$app->params['MaxNumber'];
        $this->options['boxId'] = isset($this->options['boxId']) ? $this->options['boxId'] : 'picker';
        $this->options['innerHTML'] = isset($this->options['innerHTML']) ? $this->options['innerHTML'] :'<button class="btn btn-primary">选择文件</button>';
        $this->options['previewWidth'] = isset($this->options['previewWidth']) ? $this->options['previewWidth'] : '250';
        $this->options['previewHeight'] = isset($this->options['previewHeight']) ? $this->options['previewHeight'] : '150';
    }
    public function run()
    {
        $this->registerClientJs();
        $value = Html::getAttributeValue($this->model, $this->attribute);
        $content = $this->options['innerHTML'];
        $filelist = '';
        if(empty($value)) {
            $filelist = Html::tag('ul','',['class'=>'filelist']);
        } else {
            //查询数据
            $li_list = '';
            $valueArr = explode(',', $value);
            foreach ($valueArr as $key=>$val) {
                $img_url = UpFileData::showImage($val);
                $img = strpos($img_url, 'http:') === false ? (Yii::getAlias('@static') . '/' . $img_url) : $img_url;
                $li_items ='';
                $li_items .= '<li id="WU_FILE_'.$key.'">';
                $li_items .='<p class="title">1.jpg</p>';
                $li_items .='<p class="imgWrap">';
                $li_items .='<img src="'.$img.'" width="'.$this->options['previewWidth'].'" height="'.$this->options['previewHeight'].'"></p>';
                $li_items .='<p class="progress"><span></span></p>';
                $li_items .='<div class="file-panel"><span data-id="'.$val.'" class="removeItems cancel">删除</span></div></li>';
                
                $li_list .=$li_items;
            }
            
            $filelist = Html::tag('ul',$li_list,['class'=>'filelist']);
        }
        if($this->hasModel()){
           return Html::tag('div',$filelist.Html::tag('div', $content, ['id'=>$this->options['boxId']]) . Html::activeHiddenInput($this->model, $this->attribute),['id'=>'uploader']);
        }else{
            return Html::tag('div',$filelist.Html::tag('div', $content, ['id'=>$this->options['boxId']]) . Html::hiddenInput($this->name, $this->value),['id'=>'uploader']);
        }
    }

    /**
     * 注册js
     */
    private function registerClientJs()
    {
        //获取配置文件中文件类型
        $exts = $this->options['type'] == 'file' ? Yii::$app->params['file_exts'] :  Yii::$app->params['video_exts'];
        
        $exts = implode(',', $exts);
        
        $maxSize = $this->options['type'] =='file' ? Yii::$app->params['fileMaxSize'] : Yii::$app->params['videoMaxSize'];
      
        WebuploaderAsset::register($this->view);
        $web = Yii::getAlias('@static');
        $server = $this->server ?: Url::to(['webupload']);
        $swfPath = str_replace('\\', '/', Yii::getAlias('@common/widgets/swf'));
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
        chunked:true,
        chunkSize:3072000,
        fileSingleSizeLimit:{$maxSize},
        accept: {
            title: 'Files',
            extensions: '{$exts}'
        }
    });
    
    uploader.onError = function( code ) {
        if(code == 'F_EXCEED_SIZE') {
           alert( '上传文件过大，请重新选择');
           return false;
        }

    };
    
    uploader.on('beforeFileQueued',function(file){
        var size = $('#uploader ul li').size();
        if(size >={$this->options['number']}) {
           alert('上传不能超过最大值（{$this->options['number']}）');
           return false;
        }
    });
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
            addFile(file,data);
            $( '#'+file.id ).find('p.state').text('上传成功').fadeOut();
            var tempId = $( '#{$this->options['id']}' ).val();
                tempId += tempId == '' ? '' : ',';
            var newval = tempId+data.id;
            
            $( '#{$this->options['id']}' ).val(newval);
            
        }else {
           alert(data.state);
           return false;
        }
        
    });
    
   uploader.on('error',function(type){
       if(type == 'Q_TYPE_DENIED') {
          alert('上传类型支持{$exts}格式');
          return false;
       }
    });
    
    function addFile(file,data) {
       var li = $( '<li id="' + file.id + '" style="height:40px;">' +
                '<p class="title">' + file.name + '</p>' +
                '<p class="progress"><span></span></p>' +
                '</li>');
        var btns = $('<div class="file-panel"><span data-id="'+data.id+'" class="removeItems cancel">删除</span></div>').appendTo(li);
        $('.filelist').append(li); 
    }
                    
    $(function(){
         //删除
         $('body').delegate('.removeItems','click',function(){
             var data_id = $(this).attr('data-id');
             var temparr = ($( '#{$this->options['id']}' ).val()).split(',');
             if($.inArray(data_id,temparr) > -1) {
                temparr.remove(data_id);
                $( '#{$this->options['id']}' ).val(temparr.toString());
             }
             $(this).parent().parent().find('li').off().end().remove();    
         });
         //移上去显示
         $('body').delegate('#uploader .filelist li','mouseenter',function(){
              $(this).find('.file-panel').stop().animate({height: 30});     
         });
                    
         //移上去去除
         $('body').delegate('#uploader .filelist li','mouseleave',function(){
              $(this).find('.file-panel').stop().animate({height: 0});     
         });
                 
          Array.prototype.indexOf = function(val) {
            for (var i = 0; i < this.length; i++) {
                if (this[i] == val) return i;
             }
             return -1;
           };
           Array.prototype.remove = function(val) {
                var index = this.indexOf(val);
                if (index > -1) {
                    this.splice(index, 1);
                }
           };
     });
JS
        );
    }
} 