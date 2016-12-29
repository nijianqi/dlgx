function getObjectURL(e) {
    var t = null;
    return void 0 != window.createObjectURL ? t = window.createObjectURL(e) : void 0 != window.URL ? t = window.URL.createObjectURL(e) : void 0 != window.webkitURL && (t = window.webkitURL.createObjectURL(e)), t
}

function setPickerStyle(e, t) {
    var i = $(".dpicker-body").eq(t).scrollTop(),
        a = parseInt(i / 38);
    if (a === e) return e;
    var r = $(".dpicker-body").eq(t).find(".dpicker-item");
    return $(r[a + 1]).hasClass("empty") ? a : (r = $(".dpicker-body").eq(t).find(".dpicker-item").removeClass("active").removeClass("sub-active"), $(r[a + 1]).addClass("active").prev().addClass("sub-active").next().next().addClass("sub-active"), a)
}

function isLeapYear(e) {
    return !isNaN(parseInt(e)) && (e % 4 == 0 && e % 100 != 0 || e % 400 == 0)
}

function check() {
    $(".submit").attr("disabled", "disabled");
    if ($("#rank-head input[type=checkbox]").prop("checked")) {
        var val = $("input[name='fund']").val().trim();
        if (!val) {
            myalert("资金需求不能为空！");
            $(".submit").removeAttr("disabled");
            return false;
        }
        if (isNaN(val)) {
            myalert("请输入规范的数字！");
            $(".submit").removeAttr("disabled");
            return false;
        }
        if (+val > +$("input[name='fund']").attr('data-fund')) {
            myalert("资金申请超过当前等级最高申请，如需举办大型活动，请选择大型活动申请");
            $(".submit").removeAttr("disabled");
            return false;
        }
    }
    var img = $(".mark-item-img").length;
    if(!img){
        myalert("活动图片不能为空！");
        $(".submit").removeAttr("disabled");
        return false;
    }
    return $("input[name='act_name']").val().trim() ? $("input[name='act_address']").val().trim() ? $("textarea[name='act_intro']").val().trim() ? "未设置" === $("input[name='act_start_time']").val().trim() ? (alert("开始时间不能为空！"), $(".submit").removeAttr("disabled"), !1) : "未设置" !== $("input[name='act_end_time']").val().trim() || (alert("结束时间不能为空！"), $(".submit").removeAttr("disabled"), !1) : (alert("活动介绍不能为空！"), $(".submit").removeAttr("disabled"), !1) : (alert("活动地点不能为空！"), $(".submit").removeAttr("disabled"), !1) : (alert("活动名称不能为空！"), $(".submit").removeAttr("disabled"), !1)
}
$(function() {
    var status = $(".info-mask").attr('data-status');
    var msg = $(".info-mask").attr('data-msg');
    if (status === '1' || status === '-1') {
        $(".submit").attr("disabled", "disabled");
        myalert(msg);
        $(".alert-operate").click(function() {
            var url = "actList";
            location.replace(url);
        });
    }
    $("#need").click(function() {
        $("#need").attr('value', 1);
    });
    var t = 0,
        i = 0,
        a = 0,
        r = 0,
        n = [1, 3, 5, 7, 8, 10, 12];
    $(".dpicker-body").scrollTop("9"), $(".dpicker-body").eq(0).scroll(function() {
        if (t = setPickerStyle(t, 0), "2" == $(".dpicker-item.active").eq(1).text()) {
            var e = $(".dpicker-body").eq(2).find(".dpicker-item");
            isLeapYear($(".dpicker-item.active").eq(0).text()) ? e.eq(28).removeClass("none") : e.eq(28).addClass("none")
        }
    }), $(".dpicker-body").eq(1).scroll(function() {
        i = setPickerStyle(i, 1);
        var e = $(".dpicker-body").eq(2).find(".dpicker-item");
        n.indexOf(1 + i) == -1 ? (e.eq(30).addClass("none"), 1 == i && (e.eq(29).addClass("none"), isLeapYear($(".dpicker-item.active").eq(0).text()) || e.eq(28).addClass("none"))) : e.removeClass("none")
    }), $(".dpicker-body").eq(2).scroll(function() {
        a = setPickerStyle(a, 2)
    }), $(".dpicker-finish").click(function() {
        var e = $(".dpicker-item.active"),
            t = e.eq(0).text() + " 年 " + e.eq(1).text() + " 月 " + e.eq(2).text() + " 日 ";
        $(".date-picker").css({
            bottom: "-17.5rem"
        }), $(".show-dpicker input").eq(+r).val(t)
    }), $(".dpicker-cancel").click(function() {
        $(".date-picker").css({
            bottom: "-17.5rem"
        })
    }), $(".show-dpicker").click(function() {
        r = $(this).attr("data-id"), $(".date-picker").css({
            bottom: 0
        })
    }), setPickerStyle(0), $("#club-logo-set").prev("input").change(function() {
        var e = $(this).val(),
            t = e.substring(e.lastIndexOf(".") + 1);
        if (["jpg", "png", "gif", "jpeg", "bmp"].indexOf(t.toLowerCase()) == -1) return myalert("请上传图片文件！"), void $(this).val("");
        var i = getObjectURL(this.files[0]);
        i && $("#club-logo-set").attr("src", i)
    });
    $(".mark-img-button").on("change", "input[type='file']", function() {
        var i, e = $(this).val(),
            t = e.substring(e.lastIndexOf(".") + 1);
        if (["jpg", "png", "gif", "jpeg", "bmp"].indexOf(t.toLowerCase()) == -1) return myalert("请上传图片文件！"), void $(this).val("");
        if ($(".mark-item-img").length >= 6) return myalert("最多上传6张图片"), void $(this).val("");
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
    $("#rank-head input[type=checkbox]").change(function() {
        if ($(this).prop("checked")) {
            $(".rank-input-wrap input").prop('disabled', false);
            $(".rank-input-wrap textarea").prop('disabled', false);
        } else {
            $(".rank-input-wrap input").val("").prop('disabled', true);
            $(".rank-input-wrap textarea").val("").prop('disabled', true);
        }
    });

});