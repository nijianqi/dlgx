$(function () {
	$(".submit").click(function (e) {
		for (var t = 0, r = $("input[type='text']"), a = r.length; t < a; t++)if (!$(r[t]).val().trim())return myalert("信息填写不完整！"), e.preventDefault(), !1;
		if ($("input[name='member_tel']").length && !regRule.mobile.test($("input[name='member_tel']").val()))return myalert("请输入规范的手机号！"), e.preventDefault(), !1
	}), $(".scode-send-wrap button").click(function (e) {
		var t;
		if (e.preventDefault(), !(t = $(".scode-send-wrap input[type='text']").val().trim()))return myalert("手机号不能为空！"), !1;
		if (!regRule.mobile.test(t))return myalert("请输入规范的手机号！"), !1;
		$(this).addClass("active").attr("disabled", !0);
		var r = 58, a = $(this).text("59s后可重新发送"), n = setInterval(function () {
			return 0 == r ? (a.removeClass("active").text("发送验证码"), clearInterval(n), void a.removeAttr("disabled")) : void a.text(r-- + "s后可重新发送")
		}, 1e3)
	})
});
var regRule = {idcard: /(^\d{15}$)|(^\d{17}([0-9]|X)$)/, mobile: /^1\d{10}$/, mail: /^(\w-*\.*)+@(\w-?)+(\.\w{2,})+$/};