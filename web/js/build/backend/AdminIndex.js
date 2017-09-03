var AdminIndex=function(){return function(){var n=$(".del-site-caller");n.find("button").click(function(t){t.preventDefault();var e=$(this);bootbox.confirm(__t("admin","consDeleteConfirm"),function(t){if(t){e.data("id");var a=$('<input type="hidden">').attr("name",e.attr("name")).attr("value",e.attr("value"));n.append(a),n.submit()}})})}}();new AdminIndex;
//# sourceMappingURL=AdminIndex.js.map
