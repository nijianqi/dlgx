function getObjectURL(e) {
	var t = null;
	return void 0 != window.createObjectURL ? t = window.createObjectURL(e) : void 0 != window.URL ? t = window.URL.createObjectURL(e) : void 0 != window.webkitURL && (t = window.webkitURL.createObjectURL(e)), t
}
$(function () {
	var e = null;
	$(".imodify-item").each(function () {
		$(this).hasClass("change-sex") || $(this).click(function () {
			e = $(this).find('input[type="text"]'), $(".panel-wrap").removeClass("none"), $(".sex-panel").css("bottom", "-15rem"), $(".input-panel").css("bottom", "0")
		})
	}), $(".panel-wrap").click(function () {
		$(".panel-wrap").addClass("none"), $(".input-panel").css("bottom", "-7rem"), $(".sex-panel").css("bottom", "-15rem")
	}), $(".fa-close").click(function () {
		$(this).prev("input").val("")
	}), $(".input-button").click(function () {
		var t = $(".input-wrap input").val();
		return t.trim() ? (t.trim().length >= 20 && myalert("信息长度不能大于20"), e.val(t), e.prev().text(t), $(".input-panel").css("bottom", "-7rem"), void $(".panel-wrap").addClass("none")) : void myalert("信息不能为空!")
	}), $(".change-sex").click(function () {
		$(".panel-wrap").removeClass("none"), $(".input-panel").css("bottom", "-7rem"), $(".sex-panel").css("bottom", "0")
	}), $(".sex-panel > p").click(function () {
		$(".panel-wrap").addClass("none"), $(".sex-panel").css("bottom", "-15rem")
	}), $(".sex-panel-type > p").each(function (e) {
		$(this).click(function () {
			$(".panel-wrap").addClass("none"), $(".sex-panel").css("bottom", "-15rem"), 0 == e ? $(".change-sex input").val("1").prev().text("男") : $(".change-sex input").val("2").prev().text("女")
		})
	}), $(".portrait-modify input[type='file']").on("change", function () {
		var e, t = $(this).val(), n = t.substring(t.lastIndexOf(".") + 1);
		return ["jpg", "png", "gif", "jpeg", "bmp"].indexOf(n.toLowerCase()) == -1 ? (myalert("请上传图片文件！"), void $(this).val("")) : (e = getObjectURL(this.files[0]), void $(".portrait-modify img").attr("src", e))
	})
});