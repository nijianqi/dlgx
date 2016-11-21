function getObjectURL(a) {
	var b = null;
	return void 0 != window.createObjectURL ? b = window.createObjectURL(a) : void 0 != window.URL ? b = window.URL.createObjectURL(a) : void 0 != window.webkitURL && (b = window.webkitURL.createObjectURL(a)), b
}
function setPickerStyle(d) {
	var f = $(".dpicker-body").scrollTop(), c = parseInt(f / 38);
	if (c === d) {
		return d
	}
	var b = $(".dpicker-item");
	return $(b[c + 1]).hasClass("empty") ? c : (b = $(".dpicker-item").removeClass("active").removeClass("sub-active"), $(b[c + 1]).addClass("active").prev().addClass("sub-active").next().next().addClass("sub-active"), c)
}
function check() {
	$(".submit").attr("disabled", "disabled");
	return $("input[name='club_name']").val().trim() ? $("input[name='club_password']").val().trim() ? $("textarea[name='club_intro']").val().trim() ? void 0 : (alert("社团简介不能为空！"),$(".submit").removeAttr("disabled"), !1) : (alert("口令不能为空！"),$(".submit").removeAttr("disabled"), !1) : (alert("社团名称不能为空！"),$(".submit").removeAttr("disabled"), !1)
}
$(function () {
	var b = $(".info-mask").attr("data-status");
	var a = $(".info-mask").attr("data-msg");
	"1" !== b && "-1" !== b || ("1" === b ? $(".info-mask").text(a) : $(".info-mask").text(a), $(".info-mask").removeClass("none"), setTimeout(function () {
		$(".info-mask").addClass("none")
	}, 1500));
	var c = 0;
	$(".dpicker-body").scroll(function () {
		c = setPickerStyle(c)
	}), $(".dpicker-finish").click(function () {
		var d = $(".dpicker-item.active").text();
		$(".date-picker").css({bottom: "-17.5rem"}), $(".show-dpicker input").val(d)
	}), $(".dpicker-cancel").click(function () {
		$(".date-picker").css({bottom: "-17.5rem"})
	}), $(".show-dpicker").click(function () {
		$(".date-picker").css({bottom: 0})
	}), $("#club-logo-set").prev("input").change(function () {
		var f = $(this).val(), g = f.substring(f.lastIndexOf(".") + 1);
		if (["jpg", "png", "gif", "jpeg", "bmp"].indexOf(g.toLowerCase()) == -1) {
			return myalert("请上传图片文件！"), void $(this).val("")
		}
		var d = getObjectURL(this.files[0]);
		d && $("#club-logo-set").attr("src", d)
	});
	$(".mark-img-button").on("change", "input[type='file']", function () {
		var f, h = $(this).val(), d = h.substring(h.lastIndexOf(".") + 1);
		if (["jpg", "png", "gif", "jpeg", "bmp"].indexOf(d.toLowerCase()) == -1) {
			return myalert("请上传图片文件！"), void $(this).val("")
		}
		if ($(".mark-item-img").length >= 6) {
			return myalert("最多上传6张图片"), void $(this).val("")
		}
		f = getObjectURL(this.files[0]);
		var g = $("<div class='mark-item-img'>");
		g.append("<div class='mark-item-mask'>删除</div>"), $("<img>").appendTo(g).attr("src", f), g.prependTo($(".mark-publish-img")), $(this).after($("<input type='file' name='album[]'>"))
	}), $(".mark-publish-img").on("click", ".mark-item-img", function () {
		for (var d = $(this), f = 0; ;) {
			if (d = d.prev(".mark-item-img"), 0 == d.length) {
				break
			}
			f++
		}
		confirm("确认删除？") && ($(this).remove(), $(".mark-img-button input[type='file']").eq(f).remove())
	});
	setPickerStyle(0)
});