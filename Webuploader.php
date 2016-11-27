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
        $this->options['multiple'] = isset($this->options['multiple']) ? $this->options['multiple'] : 1;
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
        $web = \Yii::getAlias('@static');
        
        $maxSize = \Yii::$app->params['imageMaxSize'];
        
        $server = $this->server ?: Url::to([
            'webupload'
        ]);
        $swfPath = str_replace('\\', '/', \Yii::getAlias('@common/widgets/swf'));
        
        WebuploaderAsset::register($this->view);
        $this->view->registerJs(<<<JS
            initUploadImage({$this->options['multiple']});
JS
        );
        if ($this->options['multiple'] == 1) {
            $this->view->registerJs(<<<HEADJS
            var previewWidth = '{$this->options['previewWidth']}';
            var previewHeight = '{$this->options['previewHeight']}';
            var webUrl = '{$web}';
            var swfPath = '{$swfPath}';
            var serverUrl = '{$server}';
HEADJS
, 1);
        }
            $this->view->registerJs(<<<HEADJS
            var inputId{$this->options['multiple']} = "{$this->options['id']}";
            var boxId{$this->options['multiple']} =  "{$this->options['boxId']}";
            var maxSize{$this->options['multiple']}  = '"{$maxSize}';
HEADJS
        ,1);
    }
} 