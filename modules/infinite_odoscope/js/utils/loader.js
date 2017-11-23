(function(document) {
  "use strict";

  var url = "%TARGET_SCRIPT_PATH%",
    scripts = document.getElementsByTagName("script"),
    thisScript = scripts[scripts.length - 1],
    thisParent = thisScript.parentNode,
    newScript = document.createElement("script"),
    currentHour = Math.floor(new Date().getTime()/(60*60*1000));

  newScript.src = url + "?" + currentHour;
  thisParent.insertBefore(newScript, thisScript.nextSibling);
})(document);
