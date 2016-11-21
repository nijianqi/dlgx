function getObjectURL(t) {
    var i = null;
    return void 0 != window.createObjectURL ? i = window.createObjectURL(t) : void 0 != window.URL ? i = window.URL.createObjectURL(t) : void 0 != window.webkitURL && (i = window.webkitURL.createObjectURL(t)), i
}
$(function () {
    $("#banner-wrap").removeClass("none");
    $.mggScrollImg(".imgbox ul", {
        loop: !0, auto: !0, callback: function (t) {
            $(".page li").eq(t).addClass("active").siblings().removeClass("active")
        }
    });
    $("#uportrait-list").css("width", 7 * $(".uportrait-wrap").length + "rem"), $("#uportrait-list").swipeRight(function () {
        var t = $(this), i = parseInt(t.css("left"));
        i = i + 130 > 0 ? 0 : i + 130, t.css("left", i + "px")
    }), $("#uportrait-list").swipeLeft(function () {
        var t = $(this), i = parseInt(t.css("left")), e = t.parent("ul").width(), a = t.width();
        i = i - 130 < e - a ? e - a : i - 130, t.css("left", i + "px")
    }), $("#onact-apply").click(function () {
        myalert("恭喜您！成功加入“大学生创业社团”！")
    }), $("#offact-apply").click(function () {
        myalert("恭喜您！报名成功啦！")
    }), $(".navbt-item").eq(0).click(function () {
        $(".mark-publish-wrap").css("bottom", "0")
    }), $(".mark-publish-head span.fl").click(function () {
        $(".mark-publish-wrap").css("bottom", "-21rem")
    }), $(".mark-list").on("click", ".mark-reply > span", function () {
        l = $(this).attr("value");
        $("#comment_id").attr('value', l);
        $(".mark-publish-wrap").css("bottom", "0")
    }), $(".mark-publish-head span.fr").click(function () {
        var t = $(".mark-publish-body > textarea").val().trim();
        return "" == t ? void myalert("评论不能为空！") : void $(".mark-publish-wrap").css("bottom", "-21rem")
    }),    $(".mark-img-button").on("change", "input[type='file']", function () {
        var i, e = $(this).val(), t = e.substring(e.lastIndexOf(".") + 1);
        if (["jpg", "png", "gif", "jpeg", "bmp"].indexOf(t.toLowerCase()) == -1)return alert("请上传图片文件！"), void $(this).val("");
        if ($(".mark-publish-body img").length >= 7)return alert("最多上传6张图片"), void $(this).val("");
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