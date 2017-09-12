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