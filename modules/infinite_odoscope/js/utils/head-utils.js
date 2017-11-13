window.oscCallbackCalls = [];

window.oscSplitTestCallback = function () {
  window.oscCallbackCalls.push(
    {
      functionName: 'oscSplitTestCallback',
      arguments: [].slice.call(arguments)
    }
  );
};

window.oscInfiniteBlockViewUpdated = function () {
  window.oscCallbackCalls.push(
    {
      functionName: 'oscInfiniteBlockViewUpdated',
      arguments: [].slice.call(arguments)
    }
  );
};

window.oscTeaserElementReplaced = function () {
  window.oscCallbackCalls.push(
    {
      functionName: 'oscTeaserElementReplaced',
      arguments: [].slice.call(arguments)
    }
  );
};

window.oscInitializeArticlePageRendering = function () {
  window.oscCallbackCalls.push(
    {
      functionName: 'oscInitializeArticlePageRendering',
      arguments: [].slice.call(arguments)
    }
  );
};

window.oscSaveTracking = function() {
  window.oscCallbackCalls.push(
    {
      functionName: 'oscSaveTracking',
      arguments: [].slice.call(arguments)
    }
  );
};