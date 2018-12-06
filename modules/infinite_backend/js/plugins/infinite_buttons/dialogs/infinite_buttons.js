CKEDITOR.dialog.add('infinite_buttons', editor => {
  const lang = editor.lang.infinite_buttons;

  return {
    title: 'Button Link',
    minWidth: 500,
    minHeight: 150,
    resizable: false,
    contents: [
      {
        id: 'info',
        label: lang.tabInfo,
        accessKey: 'I',
        elements: [
          {
            type: 'hbox',
            widths: ['50%', '50%'],
            children: [
              {
                id: 'btntype',
                type: 'select',
                label: lang.buttonStyleLabel,
                items: [
                  [lang.buttonBgColored, 'button--bg-colored-default'],
                  [lang.buttonLinedColored, 'button--lined-colored-default'],
                  [lang.buttonLinedGray, 'button--lined-gray-default'],
                  [lang.buttonLinedLight, 'button--lined-light'],
                  [lang.buttonPlainedColored, 'button--plain-colored-default'],
                  [lang.buttonPlainGray, 'button--plain-gray-default'],
                ],
                setup: function(widget) {
                  this.setValue(
                    widget.data.btntype || 'button--bg-colored-default'
                  );
                },
                commit: function(widget) {
                  widget.setData('btntype', this.getValue());
                },
              },
              {
                id: 'btnsize',
                type: 'select',
                label: lang.buttonSizeLabel,
                items: [
                  [lang.buttonSizeSmall, 'button--sm'],
                  [lang.buttonSizeMedium, 'button--md'],
                  [lang.buttonSizeLarge, 'button--lg'],
                  [lang.buttonSizeExtraLarge, 'button--xl'],
                ],
                setup: function(widget) {
                  this.setValue(widget.data.btnsize || 'button--lg');
                },
                commit: function(widget) {
                  widget.setData('btnsize', this.getValue());
                },
              },
            ],
          },
          {
            type: 'hbox',
            widths: ['50%', '50%'],
            children: [
              {
                id: 'text',
                type: 'text',
                width: '200px',
                required: true,
                label: lang.buttonTextLabel,
                setup: function(widget) {
                  this.setValue(widget.data.text || 'A Button');
                },
                commit: function(widget) {
                  widget.setData('text', this.getValue());
                },
              },
              {
                id: 'href',
                type: 'text',
                width: '200px',
                required: true,
                label: lang.buttonUrlLabel,
                setup: function(widget) {
                  this.setValue(widget.data.href || '#');
                },
                commit: function(widget) {
                  widget.setData('href', this.getValue());
                },
              },
            ],
          },
        ],
      },
      {
        id: 'target',
        label: lang.tabTarget,
        elements: [
          {
            id: 'target',
            type: 'select',
            label: lang.buttonTargetLabel,
            items: [
              ['Same Window (_self)', '_self'],
              ['New Window (_blank)', '_blank'],
              ['Topmost Window (_top)', '_top'],
              ['Parent Window (_parent)', '_parent'],
            ],
            setup: function(widget) {
              this.setValue(widget.data.target || '_self');
            },
            commit: function(widget) {
              widget.setData('target', this.getValue());
            },
          },
        ],
      },
      {
        id: 'icons',
        label: lang.tabIcons,
        elements: [
          {
            type: 'hbox',
            widths: ['50%', '50%'],
            children: [
              {
                type: 'vbox',
                children: [
                  {
                    type: 'html',
                    html:
                      '<strong>Custom Icons</strong>' +
                      '<p>e.g. <em>glyphicon-pencil</em></p>',
                  },
                  {
                    id: 'customiconsleft',
                    type: 'text',
                    width: '150px',
                    label: 'Left Icon',
                    setup: function(widget) {
                      this.setValue(widget.data.customiconsleft || '');
                    },
                    commit: function(widget) {
                      widget.setData('customiconsleft', this.getValue());
                    },
                  },
                  {
                    id: 'customiconsright',
                    type: 'text',
                    width: '150px',
                    label: 'Right Icon',
                    setup: function(widget) {
                      this.setValue(widget.data.customiconsright || '');
                    },
                    commit: function(widget) {
                      widget.setData('customiconsright', this.getValue());
                    },
                  },
                ],
              },
              {
                type: 'vbox',
                children: [
                  {
                    type: 'html',
                    html:
                      '<strong>Font Awesome</strong>' +
                      '<p><a href="http://fortawesome.github.io/Font-Awesome/cheatsheet/" target="_blank" style="padding: 0px; vertical-align: top;">List of Icons</a></p><br/>' +
                      '<p>e.g. <em>fa-arrow-right</em></p>',
                  },
                  {
                    id: 'faiconleft',
                    type: 'text',
                    width: '150px',
                    label: 'Left Icon',
                    setup: function(widget) {
                      this.setValue(widget.data.faiconleft || '');
                    },
                    commit: function(widget) {
                      widget.setData('faiconleft', this.getValue());
                    },
                  },
                  {
                    id: 'faiconright',
                    type: 'text',
                    width: '150px',
                    label: 'Right Icon',
                    setup: function(widget) {
                      this.setValue(widget.data.faiconright || '');
                    },
                    commit: function(widget) {
                      widget.setData('faiconright', this.getValue());
                    },
                  },
                ],
              },
            ],
          },
        ],
      },
    ],
  };
});
