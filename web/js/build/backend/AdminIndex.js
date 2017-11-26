var AdminIndex=function(){return function(){var n=$(".delSiteCaller");n.find("button").click(function(t){t.preventDefault();var e=$(this);bootbox.confirm(__t("admin","consDeleteConfirm"),function(t){if(t){var i=$('<input type="hidden">').attr("name",e.attr("name")).attr("value",e.attr("value"));n.append(i),n.submit()}})})}}();new AdminIndex;
//# sourceMappingURL=AdminIndex.js.map
