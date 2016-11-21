function getObjectURL(t) {
	var a = null;
	return void 0 != window.createObjectURL ? a = window.createObjectURL(t) : void 0 != window.URL ? a = window.URL.createObjectURL(t) : void 0 != window.webkitURL && (a = window.webkitURL.createObjectURL(t)), a
}
$(function () {
	$("#topic-detail-bottom").on("click", ".fa-heart", function (t) {
		var a = $(this);
		setTimeout(function () {
			a.removeClass("fa-heart").addClass("fa-heart-o").parent().removeClass("active");
			var l = a.parent().children('span').text();
			a.parent().children('span').text(parseInt(l)-1);
		}, 10)
	}), $("#topic-detail-bottom").on("click", ".fa-heart-o", function () {
		var t = $(this);
		setTimeout(function () {
			t.addClass("fa-heart").removeClass("fa-heart-o").parent().addClass("active");
			var l = t.parent().children('span').text();
			t.parent().children('span').text(parseInt(l)+1);
		}, 10)
	}), $("#topic-detail-bottom").on("click", ".fa-star-o", function () {
		var t = $(this);
		setTimeout(function () {
			t.removeClass("fa-star-o").addClass("fa-star").parent().addClass("active")
		}, 10)
	}), $("#topic-detail-bottom").on("click", ".fa-star", function () {
		var t = $(this);
		setTimeout(function () {
			t.removeClass("fa-star").addClass("fa-star-o").parent().removeClass("active")
		}, 10)
	}), $(".topic-ibottom-item").eq(0).click(function () {
		$(".mark-publish-wrap").css("bottom", "0")
	}), $(".mark-publish-head span.fl").click(function () {
		$(".mark-publish-wrap").css("bottom", "-21rem")
	}), $(".mark-list").on("click", ".mark-reply > span", function () {
		l = $(this).attr("value");
		$("#comment_id").attr('value', l);
		$(".mark-publish-wrap").css("bottom", "0")
	}),  $(".mark-img-button").on("change", "input[type='file']", function () {
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