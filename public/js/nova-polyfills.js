(function () {
  var V = window.Vue;
  if (!V) {
    window.Vue = {};
    V = window.Vue;
  }
  if (typeof V.use !== 'function') {
    V.use = function () {};
  }
})();