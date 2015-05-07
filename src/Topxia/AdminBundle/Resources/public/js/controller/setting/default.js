define(function(require, exports, module) {

    var Validator = require('bootstrap.validator');
    require('common/validator-rules').inject(Validator);

    var WebUploader = require('edusoho.webuploader');
    var Notify = require('common/bootstrap-notify');

    exports.run = function() {

        var $defaultAvatar = $("[name=defaultAvatar]");

        var defaultAvatarUploader = new WebUploader({
            element: '#default-avatar-btn'
        });

        defaultAvatarUploader.on('uploadSuccess', function(file, response ) {
            var url = $("#default-avatar-btn").data("gotoUrl");
            Notify.success('上传成功！', 1);
            document.location.href = url;
        });

        $("[name=avatar]").change(function(){
            $defaultAvatar.val($("[name=avatar]:checked").val());
        });

        if ($('[name=avatar]:checked').val() == 0){
            $('#avatar-class').hide();
        }
        if ($('[name=avatar]:checked').val() == 1){
            $('#system-avatar-class').hide();
        }

        $("[name=avatar]").on("click",function(){
            if($("[name=avatar]:checked").val()==0){
                $('#system-avatar-class').show();
                $('#avatar-class').hide();
            }
            if($("[name=avatar]:checked").val()==1){
                $('#system-avatar-class').hide();
                $('#avatar-class').show();
                defaultAvatarUploader.enable();
            }
        });

        if ($('#system-course-picture-class').length > 0) {
            
            var defaultCoursePicUploader = new WebUploader({
                element: '#default-course-picture-btn'
            });

            defaultCoursePicUploader.on('uploadSuccess', function(file, response ) {
                var url = $("#default-course-picture-btn").data("gotoUrl");
                Notify.success('上传成功！', 1);
                document.location.href = url;
            });
            
            var $systemCoursePictureClass = $('#system-course-picture-class');

            if ($('[name=coursePicture]:checked').val() == 0) {
                $('#course-picture-class').hide();
            }
            if ($('[name=coursePicture]:checked').val() == 1) {
                $systemCoursePictureClass.hide();
            }

            $("[name=coursePicture]").on("click",function(){
                if($("[name=coursePicture]:checked").val()==0){
                    $systemCoursePictureClass.show();
                    $('#course-picture-class').hide();
                }
                if($("[name=coursePicture]:checked").val()==1){
                    $systemCoursePictureClass.hide();
                    $('#course-picture-class').show();
                    defaultCoursePicUploader.enable();
                }
            });
            var $defaultCoursePicture = $("[name=defaultCoursePicture]");
            $("[name=coursePicture]").change(function(){
                $defaultCoursePicture.val($("[name=coursePicture]:checked").val());
            });
        };

    };

});