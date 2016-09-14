function initUpload(){
	var size = $("#"+boxId+" input[name='file']").size();
	if(size >0) return false;
		 var uploader = WebUploader.create({
		        auto: true,
		        fileVal: 'upfile',
		        // swf文件路径
		        swf: swfPath+'/Uploader.swf',

		        // 文件接收服务端。gy
		        server: serverUrl,

		        // 选择文件的按钮。可选。
		        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
		        pick: {
		            id:'#'+boxId,
		            //innerHTML:'{$this->options['innerHTML']}'
		            multiple:false,
		        },
		        compress:false,//配置压缩的图片的选项。如果此选项为false, 则图片在上传前不进行压缩。
		        chunked:true,// [默认值：false] 是否要分片处理大文件上传。
		        chunkSize:3072000,//[默认值：5242880] 如果要分片，分多大一片？ 默认大小为5M.
		        fileSingleSizeLimit:maxSize,//验证单个文件大小是否超出限制, 超出则不允许加入队列。
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
		            $( '#'+boxId+' .webuploader-pick' ).html('<img src="'+webUrl+''+data.url+'" width="'+previewWidth+'" height="'+previewHeight+'"/>');
		            $( '#'+inputId ).val(data.id);
		            $( '#'+boxId+' .webuploader-pick' ).siblings('div').width(previewWidth).height(previewHeight);
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
		    uploader = null;   
}