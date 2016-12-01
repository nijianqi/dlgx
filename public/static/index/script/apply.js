$(function() {
	var status = $(".info-mask").attr('data-status');
	var msg = $(".info-mask").attr("data-msg");
	if (status === '1' || status === '-1') {
		$(".submit").attr("disabled", "disabled");
		if (status === '1') {
			$(".info-mask").text('报名成功,请等待审核!');
		} else {
			$(".info-mask").text(msg);
		}
		$(".info-mask").removeClass('none');
		setTimeout(function() {
			$(".info-mask").addClass('none');
		}, 1500);
	}
	var rate = 0;
	$(".dpicker-body").scroll(function() {
		rate = setPickerStyle(rate)
	});

	$(".dpicker-finish").click(function() {
		var text = $(".dpicker-item.active").text();
		$(".date-picker").css({
			'bottom': '-17.5rem'
		});
		$(".show-dpicker input").val(text);
	});

	$(".dpicker-cancel").click(function() {
		$(".date-picker").css({
			'bottom': '-17.5rem'
		});
	});

	$(".show-dpicker").click(function() {
		$(".date-picker").css({
			'bottom': 0
		});
	});

	$(".mark-img-button").on("change", "input[type='file']", function() {
		var i, e = $(this).val(),
			t = e.substring(e.lastIndexOf(".") + 1);
		if (["jpg", "png", "gif", "jpeg", "bmp"].indexOf(t.toLowerCase()) == -1) return myalert("请上传图片文件！"), void $(this).val("");
		if ($(".mark-item-img").length >= 3) return myalert("最多上传3张图片"), void $(this).val("");
		i = getObjectURL(this.files[0]);
		var r = $("<div class='mark-item-img'>");
		r.append("<div class='mark-item-mask'>删除</div>"), $("<img>").appendTo(r).attr("src", i), r.prependTo($(".mark-publish-img")), $(this).after($("<input type='file' name='album[]'>"))
	}), $(".mark-publish-img").on("click", ".mark-item-img", function() {
		for (var i = $(this), e = 0;;) {
			if (i = i.prev(".mark-item-img"), 0 == i.length) break;
			e++
		}
		confirm("确认删除？") && ($(this).remove(), $(".mark-img-button input[type='file']").eq(e).remove())
	});

	setPickerStyle(0);
})

function getObjectURL(file) {
	var url = null;
	if (window.createObjectURL != undefined) { // basic
		url = window.createObjectURL(file);
	} else if (window.URL != undefined) { // mozilla(firefox)
		url = window.URL.createObjectURL(file);
	} else if (window.webkitURL != undefined) { // webkit or chrome
		url = window.webkitURL.createObjectURL(file);
	}
	return url;
}

function setPickerStyle(rate) {
	var i, len,
		scrollTop = $(".dpicker-body").scrollTop(),
		newRate = parseInt(scrollTop / 38);

	if (newRate === rate) {
		return rate;
	}

	var itemList = $(".dpicker-item");

	if ($(itemList[newRate + 1]).hasClass('empty')) {
		return newRate;
	}

	itemList = $(".dpicker-item").removeClass('active').removeClass('sub-active');

	$(itemList[newRate + 1]).addClass('active').prev().addClass('sub-active').next().next().addClass('sub-active');

	return newRate;
}

function check() {
	$(".submit").attr("disabled", "disabled");
	if (!$("input[name='cp_name']").val().trim()) {
		alert("“CP”名称不能为空！");
		$(".submit").removeAttr("disabled");
		return false;
	}
	if (!$("input[name='cp_type']").val().trim()) {
		alert("“CP关系”不能为空！");
		$(".submit").removeAttr("disabled");
		return false;
	}
	if (!$("textarea[name='cp_slogan']").val().trim()) {
		alert("参赛口号不能为空！");
		$(".submit").removeAttr("disabled");
		return false;
	}
	if ($(".mark-img-button input[type='file']").length == 1) {
		alert("请至少上传一张合照！");
		$(".submit").removeAttr("disabled");
		return false;
	}
	return true;
}