function getObjectURL(t) {
    var a = null;
    return void 0 != window.createObjectURL ? a = window.createObjectURL(t) : void 0 != window.URL ? a = window.URL.createObjectURL(t) : void 0 != window.webkitURL && (a = window.webkitURL.createObjectURL(t)), a
}
$(function () {
    $("#topic-list").on("click", ".topic-ibottom-item:first-child", function () {
        var t = $(this).attr("value");
        $("#topic_id").attr('value', t);
        $(".mark-publish-wrap").css("bottom", "0")
    }), $(".mark-publish-head span.fl").click(function () {
        $(".mark-publish-wrap").css("bottom", "-21rem")
    }), $(".mark-publish-head span.fr").click(function () {
        var t = $(".mark-publish-body > textarea").val().trim();
        if(t ==""){
            myalert("评论不能为空！")
        }else{
            $("#uploadFrom").submit();
            myalert('评论成功');
            $(".mark-publish-wrap").css("bottom", "-21rem");
            $(".alert-operate").click(function () {
                location.reload();
            });
        }
    }), $(".mark-img-button").on("change", "input[type='file']", function () {
        var i, e = $(this).val(), t = e.substring(e.lastIndexOf(".") + 1);
        if (["jpg", "png", "gif", "jpeg", "bmp"].indexOf(t.toLowerCase()) == -1)return myalert("请上传图片文件！"), void $(this).val("");
        if ($(".mark-publish-body img").length >= 7)return myalert("最多上传6张图片"), void $(this).val("");
        i = getObjectURL(this.files[0]);
        var r = $("<div class='mark-item-img'>");
        r.append("<div class='mark-item-mask'>删除</div>"), $("<img>").appendTo(r).attr("src", i), r.prependTo($(".mark-publish-img")), $(this).after($("<input type='file' name='file[]'>"))
    }), $(".mark-publish-img").on("click", ".mark-item-img", function () {
        for (var i = $(this), e = 0; ;) {
            if (i = i.prev(".mark-item-img"), 0 == i.length)break;
            e++
        }
        confirm("确认删除？") && ($(this).remove(), $(".mark-img-button input[type='file']").eq(e).remove())
    })
});
