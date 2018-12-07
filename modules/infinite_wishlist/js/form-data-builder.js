/**
 * https://github.com/knowledgecode/formdatabuilder/blob/master/formdatabuilder.js
 * @preserve formdatabuilder.js (c) 2015 KNOWLEDGECODE | MIT
 */
(function(global) {
  const FormDataBuilder = function() {
    this.boundary =
      '----WebKitFormBoundary' +
      Math.random()
        .toString(36)
        .slice(2);
    this.type = 'multipart/form-data; boundary=' + this.boundary;
    this.crlf = '\r\n';
    this.pairs = [];
  };

  FormDataBuilder.prototype.append = function(name, value) {
    const type = Object.prototype.toString.call(value);

    const enc = function(str) {
      // WebKit's behavior
      return str
        .replace(/\r/g, '%0D')
        .replace(/\n/g, '%0A')
        .replace(/"/g, '%22');
    };

    const pair = {
      disposition: 'form-data; name="' + enc(name || '') + '"',
    };

    // WebKit's behavior
    if (!name) {
      return;
    }
    if (type === '[object File]' || type === '[object Blob]') {
      pair.disposition += '; filename="' + enc(value.name || 'blob') + '"';
      pair.type = value.type || 'application/octet-stream';
      pair.value = value;
    } else {
      pair.value = String(value);
    }
    this.pairs.push(pair);
  };

  FormDataBuilder.prototype.getBlob = function() {
    const array = [];

    let i;

    const len = this.pairs.length;

    for (i = 0; i < len; i++) {
      array.push(
        '--' +
          this.boundary +
          this.crlf +
          'Content-Disposition: ' +
          this.pairs[i].disposition
      );
      if (this.pairs[i].type) {
        array.push('' + this.crlf + 'Content-Type: ' + this.pairs[i].type);
      }
      array.push(this.crlf + this.crlf);
      array.push(this.pairs[i].value);
      array.push(this.crlf);
    }
    array.push('--' + this.boundary + '--' + this.crlf);
    return global.Blob
      ? new Blob(array)
      : new global.FileReaderSync().readAsArrayBuffer(
          (function(data) {
            const Builder =
              global.BlobBuilder ||
              global.WebKitBlobBuilder ||
              global.MSBlobBuilder;

            const blob = new Builder();

            (data || []).forEach(function(d) {
              blob.append(d);
            });
            return blob.getBlob();
          })(array)
        );
  };

  global.FormDataBuilder = FormDataBuilder;
})(this);
