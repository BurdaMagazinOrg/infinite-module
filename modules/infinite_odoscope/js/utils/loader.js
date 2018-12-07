(function(document) {
  const url = '%TARGET_SCRIPT_PATH%';

  const scripts = document.getElementsByTagName('script');

  const thisScript = scripts[scripts.length - 1];

  const thisParent = thisScript.parentNode;

  const newScript = document.createElement('script');

  const currentHour = Math.floor(new Date().getTime() / (60 * 60 * 1000));

  newScript.src = '' + url + '?' + currentHour;
  thisParent.insertBefore(newScript, thisScript.nextSibling);
})(document);
