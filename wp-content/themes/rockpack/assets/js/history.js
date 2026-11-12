/* Keyboard-accessible milestone navigation; shared navigation lives in inner-page.js. */
(function () {
  'use strict';
  var timeline = document.querySelector('.history-timeline');
  if (!timeline) return;
  var track = timeline.querySelector('.history-timeline__track');
  var previous = document.querySelector('.history-controls__prev');
  var next = document.querySelector('.history-controls__next');
  var status = document.querySelector('.history-status');
  var isAnimating = false;
  if (track.children.length < 2) { previous.disabled = true; next.disabled = true; }
  // Preserve the design's row sizes, and expand them for narrow screens or longer CMS copy.
  function fitRows() {
    timeline.style.removeProperty('--history-upper');
    timeline.style.removeProperty('--history-lower');
    var rows = getComputedStyle(track.firstElementChild).gridTemplateRows.split(' ').map(parseFloat);
    var bounds = timeline.getBoundingClientRect();
    Array.prototype.forEach.call(track.children, function (item) {
      var rect = item.getBoundingClientRect();
      if (rect.right <= bounds.left || rect.left >= bounds.right) return;
      item.querySelectorAll('.history-timeline__content, .history-timeline__media').forEach(function (element) {
        var style = getComputedStyle(element);
        var row = parseInt(style.gridRowStart, 10) - 1;
        var required = element.offsetHeight + parseFloat(style.marginTop) + parseFloat(style.marginBottom) + 16;
        rows[row] = Math.max(rows[row], required);
      });
    });
    timeline.style.setProperty('--history-upper', Math.ceil(rows[0]) + 'px');
    timeline.style.setProperty('--history-lower', Math.ceil(rows[1]) + 'px');
  }
  function advance(direction) {
    if (track.children.length < 2 || isAnimating) return;
    isAnimating = true;

    function finish(forward) {
      if (forward) track.appendChild(track.firstElementChild);
      track.classList.remove('is-sliding-next', 'is-sliding-prev', 'is-preparing-prev');
      status.textContent = track.firstElementChild.querySelector('h3').textContent;
      fitRows();
      isAnimating = false;
    }

    function afterTransform(callback) {
      var complete = function (event) {
        if (event.target !== track || event.propertyName !== 'transform') return;
        track.removeEventListener('transitionend', complete);
        callback();
      };
      track.addEventListener('transitionend', complete);
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      if (direction > 0) track.appendChild(track.firstElementChild);
      else track.insertBefore(track.lastElementChild, track.firstElementChild);
      finish(false);
      return;
    }

    if (direction > 0) {
      afterTransform(function () { finish(true); });
      track.classList.add('is-sliding-next');
      return;
    }

    track.insertBefore(track.lastElementChild, track.firstElementChild);
    track.classList.add('is-preparing-prev');
    // Commit the off-screen starting position before transitioning it in.
    void track.offsetWidth;
    afterTransform(function () { finish(false); });
    track.classList.remove('is-preparing-prev');
    track.classList.add('is-sliding-prev');
  }
  previous.addEventListener('click', function () { advance(-1); });
  next.addEventListener('click', function () { advance(1); });
  timeline.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault(); advance(event.key === 'ArrowRight' ? 1 : -1);
    }
  });
  window.addEventListener('resize', fitRows);
  if (document.fonts) document.fonts.ready.then(fitRows);
  fitRows();
}());
