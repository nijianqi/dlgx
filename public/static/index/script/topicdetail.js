function getObjectURL(c) {
	var b = null;
	return void 0 != window.createObjectURL ? b = window.createObjectURL(c) : void 0 != window.URL ? b = window.URL.createObjectURL(c) : void 0 != window.webkitURL && (b = window.webkitURL.createObjectURL(c)), b
}
$(function() {
	$(".topic-ibottom-item").eq(0).click(function() {
		$(".mark-publish-wrap").css("bottom", "0")
	}), $(".mark-publish-head span.fl").click(function() {
		$(".mark-publish-wrap").css("bottom", "-21rem")
	}), $(".mark-list").on("click", ".mark-reply > span", function() {
		l = $(this).attr("value");
		$("#comment_id").attr("value", l);
		$(".mark-publish-wrap").css("bottom", "0")
	}), $(".mark-img-button").on("change", "input[type='file']", function() {
		var b, d = $(this).val(),
				a = d.substring(d.lastIndexOf(".") + 1);
		if (["jpg", "png", "gif", "jpeg", "bmp"].indexOf(a.toLowerCase()) == -1) {
			return myalert("请上传图片文件！"), void $(this).val("")
		}
		if ($(".mark-publish-body img").length >= 7) {
			return myalert("最多上传6张图片"), void $(this).val("")
		}
		b = getObjectURL(this.files[0]);
		var c = $("<div class='mark-item-img'>");
		c.append("<div class='mark-item-mask'>删除</div>"), $("<img>").appendTo(c).attr("src", b), c.prependTo($(".mark-publish-img")), $(this).after($("<input type='file' name='file[]'>"))
	}), $(".mark-publish-img").on("click", ".mark-item-img", function() {
		for (var a = $(this), b = 0;;) {
			if (a = a.prev(".mark-item-img"), 0 == a.length) {
				break
			}
			b++
		}
		confirm("确认删除？") && ($(this).remove(), $(".mark-img-button input[type='file']").eq(b).remove())
	})
});