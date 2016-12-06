$(function() {
	var status = $(".info-mask").attr('data-status');
	var msg = $(".info-mask").attr("data-msg");
	if (status === '1' || status === '-1') {
		$(".submit").attr("disabled", "disabled");
		if (status === '1') {
			myalert('报名成功,请等待审核!');
			 $(".alert-operate").click(function () {
                           location.href = "{:url('vote/vote')}";
                        });
		} else {
			myalert(msg);
		}
		$(".info-mask").removeClass('none');
		setTimeout(function() {
			$(".info-mask").addClass('none');
		}, 1500);
	}
var rate0 = 0,
 	rate1 = 0;
 	$(".dpicker-body0").scroll(function() {
 	rate0 = setPickerStyle(rate0, 0)
 	});
 $(".dpicker-body1").scroll(function() {
 	rate1 = setPickerStyle(rate1, 1)
	});

	$(".dpicker-finish").click(function() {
	var text = $(this).parents('.date-picker').find('.dpicker-item.active').text();
 if ($(this).hasClass('dpicker-finish1')) {
 $(".date-picker1").css({
 'bottom': '-17.5rem'
 });
 $(".show-dpicker1 input").val(text);
 } else {
 $(".date-picker0").css({
 'bottom': '-17.5rem'
 });
 $(".show-dpicker input").val(text);
}
	});

	$(".dpicker-cancel").click(function() {
		$(this).parents('.date-picker').css({
			'bottom': '-17.5rem'
		});
	});

	$(".show-dpicker").click(function() {
		$(".date-picker1").css({
 			'bottom': '-17.5rem'
 		});
 		$(".date-picker0").css({
			'bottom': 0
		});
	});

	$(".show-dpicker1").click(function() {
 $(".date-picker1").css({
 'bottom': 0
 });
 $(".date-picker0").css({
 'bottom': '-17.5rem'
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

function setPickerStyle(rate, flag) {
	var i, len,
		scrollTop = flag == 0 ? $(".dpicker-body0").scrollTop() : $(".dpicker-body1").scrollTop(),
		newRate = parseInt(scrollTop / 38);

	if (newRate === rate) {
		return rate;
	}

var itemList = flag == 0 ? $(".date-picker0 .dpicker-item") : $(".date-picker1 .dpicker-item");

	if ($(itemList[newRate + 1]).hasClass('empty')) {
		return newRate;
	}

	itemList = flag == 0 ? $(".date-picker0 .dpicker-item").removeClass('active').removeClass('sub-active') : $(".date-picker1 .dpicker-item").removeClass('active').removeClass('sub-active');
  

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
	if (!$("textarea[name='cp_topic']").val().trim()) {
		alert("话题内容不能为空！");
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