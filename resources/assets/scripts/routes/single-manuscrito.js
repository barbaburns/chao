import OpenSeadragon from 'openseadragon';

function getPages(pagesScript) {
  if (!pagesScript) {
    return [];
  }

  try {
    return JSON.parse(pagesScript.textContent || '[]');
  } catch (error) {
    console.error('Failed to parse manuscript pages payload.', error);
    return [];
  }
}

function getTileSource(page) {
  return {
    type: 'image',
    url: page.imageUrl,
    buildPyramid: false,
  };
}

function clearOverlays(osd, overlayIds) {
  overlayIds.forEach((overlayId) => {
    osd.removeOverlay(overlayId);
  });

  return [];
}

function getOverlayRect(page, box) {
  const pageWidth = Number(page.pageWidth || 0);
  const pageHeight = Number(page.pageHeight || 0);
  const x = Number(box.x || 0);
  const y = Number(box.y || 0);
  const width = Number(box.width || 0);
  const height = Number(box.height || 0);

  if (pageWidth > 0 && pageHeight > 0) {
    return new OpenSeadragon.Rect(
      x / pageWidth,
      y / pageWidth,
      width / pageWidth,
      height / pageWidth
    );
  }

  return new OpenSeadragon.Rect(x, y, width, height);
}

function renderHighlightOverlays(osd, page, overlayIds) {
  const nextOverlayIds = clearOverlays(osd, overlayIds);

  (page.highlightBoxes || []).forEach((box, index) => {
    const width = Number(box.width || 0);
    const height = Number(box.height || 0);

    if (width <= 0 || height <= 0) {
      return;
    }

    const element = document.createElement('span');
    const overlayId = `manuscript-highlight-${page.pageNumber}-${index}`;

    element.className = 'manuscript-single__viewer-highlight';
    element.dataset.manuscriptHighlightLayer = 'true';
    element.id = overlayId;

    osd.addOverlay({
      element,
      location: getOverlayRect(page, box),
    });

    nextOverlayIds.push(overlayId);
  });

  return nextOverlayIds;
}

export default {
  init() {
    const viewerElement = document.querySelector('[data-manuscript-viewer]');

    if (!viewerElement) {
      return;
    }

    const osdElement = viewerElement.querySelector('[data-manuscript-osd]');
    const pagesScript = viewerElement.querySelector('[data-manuscript-pages]');
    const pageLabel = viewerElement.querySelector('[data-manuscript-page-label]');
    const pageJumpForm = viewerElement.querySelector('[data-manuscript-page-jump-form]');
    const pageInput = viewerElement.querySelector('[data-manuscript-page-input]');
    const searchToggleButton = viewerElement.querySelector('[data-manuscript-search-toggle]');
    const searchResetButton = viewerElement.querySelector('[data-manuscript-search-reset]');
    const searchOverlay = viewerElement.querySelector('[data-manuscript-search-overlay]');
    const searchInput = viewerElement.querySelector('[data-manuscript-search-input]');
    const searchCloseButton = viewerElement.querySelector('[data-manuscript-search-close]');
    const searchMeta = viewerElement.querySelector('.manuscript-single__viewer-search-meta');
    const fitToggleButton = viewerElement.querySelector('[data-manuscript-fit-toggle]');
    const zoomOutButton = viewerElement.querySelector('[data-manuscript-zoom-out]');
    const zoomInButton = viewerElement.querySelector('[data-manuscript-zoom-in]');
    const fullscreenButton = viewerElement.querySelector('[data-manuscript-fullscreen]');
    const pageLinks = Array.from(document.querySelectorAll('[data-manuscript-go-page]'));
    const previousButton = viewerElement.querySelector('[data-manuscript-prev]');
    const nextButton = viewerElement.querySelector('[data-manuscript-next]');
    const viewerShell = viewerElement.querySelector('.manuscript-single__viewer-shell');
    const pages = getPages(pagesScript);
    const initialPageNumber = Number(viewerElement.dataset.initialPageNumber || 0);
    const mobileViewport = typeof window.matchMedia === 'function'
      ? window.matchMedia('(max-width: 991.98px)')
      : null;
    let swipeStart = null;
    let suppressViewerClick = false;
    const preloadedPageImages = {};
    const state = {
      activeIndex: 0,
      fitMode: 'width',
      fitModeBeforeFullscreen: 'width',
      isFullscreen: false,
      isPseudoFullscreen: false,
      mobileFullscreenFitTimer: null,
      overlayIds: [],
    };

    if (!osdElement || !pageLabel || !previousButton || !nextButton || !pages.length) {
      return;
    }

    osdElement.setAttribute('tabindex', '0');
    osdElement.setAttribute('role', 'region');
    pageLabel.setAttribute('aria-live', 'polite');
    pageLabel.setAttribute('aria-atomic', 'true');

    const osd = OpenSeadragon({
      element: osdElement,
      drawer: ['html', 'canvas', 'webgl'],
      showNavigationControl: false,
      showNavigator: false,
      preserveImageSizeOnResize: true,
      visibilityRatio: 1,
      minZoomLevel: 0.05,
      maxZoomPixelRatio: 2,
    });

    function updateViewerLabel(page) {
      const imageDescription = page.imageAlt ? ` ${page.imageAlt}` : '';

      osdElement.setAttribute(
        'aria-label',
        `Visualizador do manuscrito. Pagina ${page.pageNumber} de ${pages.length}.${imageDescription}`
      );
    }

    function updateNavState() {
      previousButton.disabled = state.activeIndex <= 0;
      nextButton.disabled = state.activeIndex >= pages.length - 1;
    }

    function updatePageInput(page) {
      if (!pageInput) {
        return;
      }

      pageInput.value = String(page.pageNumber);
      pageInput.setAttribute('max', String(pages.length));
      pageInput.setAttribute('aria-label', `Ir para a pagina. Atual: ${page.pageNumber} de ${pages.length}`);
    }

    function findPageIndex(pageNumber) {
      return pages.findIndex((page) => page.pageNumber === pageNumber);
    }

    function preloadPage(index, priority = 'low') {
      const page = pages[index];

      if (!page || !page.imageUrl || preloadedPageImages[index]) {
        return;
      }

      const image = new window.Image();

      image.decoding = 'async';
      image.fetchPriority = priority;
      image.src = page.imageUrl;
      preloadedPageImages[index] = image;
    }

    function preloadAdjacentPages(activeIndex) {
      Object.keys(preloadedPageImages).forEach((index) => {
        if (Math.abs(Number(index) - activeIndex) > 2) {
          delete preloadedPageImages[index];
        }
      });

      preloadPage(activeIndex + 1, 'high');
      preloadPage(activeIndex + 2);
      preloadPage(activeIndex - 1);
    }

    function clearPreloadedPages() {
      Object.keys(preloadedPageImages).forEach((index) => {
        delete preloadedPageImages[index];
      });
    }

    function zoomOut() {
      if (!osd.viewport) {
        return;
      }

      osd.viewport.zoomBy(1 / 1.2);
      osd.viewport.applyConstraints();
    }

    function zoomIn() {
      if (!osd.viewport) {
        return;
      }

      osd.viewport.zoomBy(1.2);
      osd.viewport.applyConstraints();
    }

    function applyFitMode(mode) {
      if (!osd.viewport) {
        return;
      }

      if (mode === 'page') {
        if (typeof osd.viewport.goHome !== 'function') {
          return;
        }

        osd.viewport.goHome(true);
        return;
      }

      if (typeof osd.viewport.fitHorizontally === 'function') {
        osd.viewport.fitHorizontally(true);
      }
    }

    function resizeViewer(callback) {
      if (typeof osd.forceResize !== 'function') {
        callback();
        return;
      }

      osd.addOnceHandler('after-resize', () => {
        if (typeof window.requestAnimationFrame === 'function') {
          window.requestAnimationFrame(callback);
          return;
        }

        window.setTimeout(callback, 0);
      });
      osd.forceResize();
    }

    function fitToPage() {
      state.fitMode = 'page';
      applyFitMode(state.fitMode);
      updateFitToggleButton();
    }

    function fitToWidth() {
      state.fitMode = 'width';
      applyFitMode(state.fitMode);
      updateFitToggleButton();
    }

    function updateFitToggleButton() {
      if (!fitToggleButton) {
        return;
      }

      const pageIcon = fitToggleButton.querySelector('.manuscript-single__viewer-fit-icon--page');
      const widthIcon = fitToggleButton.querySelector('.manuscript-single__viewer-fit-icon--width');
      const nextModeIsPage = state.fitMode !== 'page';
      const label = nextModeIsPage ? 'Ajustar pagina inteira' : 'Ajustar largura';

      if (pageIcon && widthIcon) {
        if (nextModeIsPage) {
          pageIcon.removeAttribute('hidden');
          widthIcon.setAttribute('hidden', '');
        } else {
          pageIcon.setAttribute('hidden', '');
          widthIcon.removeAttribute('hidden');
        }
      }

      fitToggleButton.setAttribute('aria-label', label);
      fitToggleButton.setAttribute('data-manuscript-tooltip', label);
    }

    function updateFullscreenButton(isFullscreen) {
      if (!fullscreenButton) {
        return;
      }

      const enterIcon = fullscreenButton.querySelector('.manuscript-single__viewer-fullscreen-icon--enter');
      const exitIcon = fullscreenButton.querySelector('.manuscript-single__viewer-fullscreen-icon--exit');

      if (enterIcon && exitIcon) {
        if (isFullscreen) {
          enterIcon.setAttribute('hidden', '');
          exitIcon.removeAttribute('hidden');
        } else {
          enterIcon.removeAttribute('hidden');
          exitIcon.setAttribute('hidden', '');
        }
      }

      fullscreenButton.setAttribute('aria-label', isFullscreen ? 'Sair da tela cheia' : 'Tela cheia');
      fullscreenButton.setAttribute('data-manuscript-tooltip', isFullscreen ? 'Sair da tela cheia' : 'Tela cheia');
    }

    function scheduleMobileFullscreenFit() {
      if (state.mobileFullscreenFitTimer) {
        window.clearTimeout(state.mobileFullscreenFitTimer);
      }

      if (!state.isFullscreen || !mobileViewport || !mobileViewport.matches) {
        state.mobileFullscreenFitTimer = null;
        return;
      }

      state.mobileFullscreenFitTimer = window.setTimeout(() => {
        state.mobileFullscreenFitTimer = null;

        if (!state.isFullscreen || state.fitMode !== 'page') {
          return;
        }

        resizeViewer(() => {
          if (state.isFullscreen && state.fitMode === 'page') {
            applyFitMode('page');
            zoomOut();
          }
        });
      }, 250);
    }

    function getNativeFullscreenElement() {
      return document.fullscreenElement || document.webkitFullscreenElement || null;
    }

    function isViewerFullscreen() {
      return state.isPseudoFullscreen || getNativeFullscreenElement() === viewerElement;
    }

    function setPseudoFullscreen(isFullscreen) {
      state.isPseudoFullscreen = isFullscreen;
      viewerElement.classList.toggle('is-pseudo-fullscreen', isFullscreen);
      document.body.classList.toggle('manuscript-viewer-fullscreen-open', isFullscreen);
      syncFullscreenState();
    }

    function syncFullscreenState() {
      const isFullscreen = isViewerFullscreen();
      const isEnteringFullscreen = isFullscreen && !state.isFullscreen;

      if (isEnteringFullscreen) {
        state.fitModeBeforeFullscreen = state.fitMode;
        state.fitMode = 'page';
      } else if (!isFullscreen && state.isFullscreen) {
        state.fitMode = state.fitModeBeforeFullscreen;
      }

      state.isFullscreen = isFullscreen;

      viewerElement.classList.toggle('is-fullscreen', isFullscreen);
      updateFullscreenButton(isFullscreen);
      updateFitToggleButton();

      if (!osd.viewport) {
        return;
      }

      resizeViewer(() => {
        applyFitMode(state.fitMode);

        if (isEnteringFullscreen) {
          zoomOut();
        }
      });
      scheduleMobileFullscreenFit();
    }

    function toggleFullscreen() {
      if (state.isPseudoFullscreen) {
        setPseudoFullscreen(false);
        return;
      }

      if (getNativeFullscreenElement() === viewerElement) {
        const exitFullscreen = document.exitFullscreen
          || document.webkitExitFullscreen
          || document.webkitCancelFullScreen;

        if (typeof exitFullscreen === 'function') {
          exitFullscreen.call(document);
        }

        return;
      }

      const requestFullscreen = viewerElement.requestFullscreen
        || viewerElement.webkitRequestFullscreen
        || viewerElement.webkitRequestFullScreen;

      if (typeof requestFullscreen !== 'function') {
        setPseudoFullscreen(true);
        return;
      }

      try {
        const fullscreenRequest = requestFullscreen.call(viewerElement);

        if (fullscreenRequest && typeof fullscreenRequest.catch === 'function') {
          fullscreenRequest.catch(() => {
            setPseudoFullscreen(true);
          });
        }
      } catch (error) {
        setPseudoFullscreen(true);
      }
    }

    function setSearchOpen(isOpen, shouldFocusInput = false) {
      if (!searchOverlay || !searchToggleButton) {
        return;
      }

      searchOverlay.hidden = !isOpen;
      viewerElement.classList.toggle('is-search-open', isOpen);
      searchToggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

      if (isOpen && searchInput) {
        searchInput.value = '';

        if (shouldFocusInput && typeof window.requestAnimationFrame === 'function') {
          window.requestAnimationFrame(() => {
            searchInput.focus();
          });
        }
      }
    }

    function toggleSearchOverlay() {
      if (!searchOverlay) {
        return;
      }

      const isOpening = searchOverlay.hidden;

      setSearchOpen(isOpening, isOpening);
    }

    function updateSearchMeta(responseViewer) {
      if (!searchMeta || !responseViewer) {
        return;
      }

      const responseMeta = responseViewer.querySelector('.manuscript-single__viewer-search-meta');

      if (responseMeta) {
        searchMeta.innerHTML = responseMeta.innerHTML;
      }
    }

    function updateSearchResetButton(requestUrl) {
      if (!searchResetButton) {
        return;
      }

      const searchQuery = requestUrl.searchParams.get('busca') || '';

      searchResetButton.hidden = searchQuery.trim() === '';
    }

    function clearRuntimeEmptyState() {
      if (!viewerShell) {
        return;
      }

      const emptyState = viewerShell.querySelector('[data-manuscript-runtime-empty]');

      if (emptyState) {
        emptyState.remove();
      }
    }

    function showRuntimeEmptyState(responseViewer) {
      if (!viewerShell) {
        return;
      }

      clearRuntimeEmptyState();

      const responseEmptyState = responseViewer
        ? responseViewer.querySelector('.manuscript-single__viewer-empty')
        : null;
      const emptyState = document.createElement('div');
      const title = responseEmptyState
        ? responseEmptyState.querySelector('.manuscript-single__viewer-empty-title')
        : null;
      const copy = responseEmptyState
        ? responseEmptyState.querySelector('.manuscript-single__viewer-empty-copy')
        : null;

      emptyState.className = 'manuscript-single__viewer-empty manuscript-single__viewer-empty--runtime';
      emptyState.dataset.manuscriptRuntimeEmpty = 'true';
      emptyState.setAttribute('role', 'status');

      if (title) {
        emptyState.appendChild(title.cloneNode(true));
      }

      if (copy) {
        emptyState.appendChild(copy.cloneNode(true));
      }

      viewerShell.appendChild(emptyState);
    }

    function replaceViewerPages(nextPages, responseViewer) {
      state.overlayIds = clearOverlays(osd, state.overlayIds);
      clearPreloadedPages();
      pages.splice(0, pages.length, ...nextPages);

      const hasPages = pages.length > 0;

      osdElement.hidden = !hasPages;
      previousButton.hidden = !hasPages;
      nextButton.hidden = !hasPages;

      if (pageJumpForm) {
        pageJumpForm.hidden = !responseViewer
          || !responseViewer.querySelector('[data-manuscript-page-jump-form]');
      }

      if (!hasPages) {
        osd.close();
        showRuntimeEmptyState(responseViewer);
        pageLabel.textContent = 'Nenhuma pagina encontrada';
        return false;
      }

      clearRuntimeEmptyState();

      const responseInitialPage = Number(responseViewer.dataset.initialPageNumber || 0);
      const responseInitialIndex = pages.findIndex((page) => page.pageNumber === responseInitialPage);

      goToPage(Math.max(0, responseInitialIndex));
      return true;
    }

    function updateViewerFromSearch(requestUrl) {
      viewerElement.setAttribute('aria-busy', 'true');

      window.fetch(requestUrl.toString(), {
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
        })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`Search request failed with status ${response.status}`);
          }

          return response.text();
        })
        .then((responseText) => {
          const responseDocument = new window.DOMParser().parseFromString(responseText, 'text/html');
          const responseViewer = responseDocument.querySelector('#manuscript-viewer');
          const responsePagesScript = responseViewer
            ? responseViewer.querySelector('[data-manuscript-pages]')
            : null;

          updateSearchMeta(responseViewer);
          updateSearchResetButton(requestUrl);
          const hasPages = replaceViewerPages(getPages(responsePagesScript), responseViewer);

          window.history.replaceState({}, '', requestUrl.toString());
          setSearchOpen(!hasPages, !hasPages);

          if (hasPages) {
            osdElement.focus();
          }
        })
        .catch((error) => {
          console.error('Failed to update manuscript search in fullscreen.', error);

          if (searchMeta) {
            searchMeta.textContent = 'Nao foi possivel concluir a busca. Tente novamente.';
          }
        })
        .then(() => {
          viewerElement.removeAttribute('aria-busy');
        });
    }

    function getSearchRequestUrl(form) {
      const requestUrl = new URL(form.action || window.location.href, window.location.href);
      const searchParams = new URLSearchParams(new window.FormData(form));

      requestUrl.search = searchParams.toString();

      return requestUrl;
    }

    function goToPage(index) {
      const boundedIndex = Math.min(Math.max(index, 0), pages.length - 1);
      const page = pages[boundedIndex];

      state.activeIndex = boundedIndex;
      state.overlayIds = clearOverlays(osd, state.overlayIds);
      preloadAdjacentPages(boundedIndex);
      osd.addOnceHandler('open', () => {
        if (state.fitMode === 'page') {
          fitToPage();
        } else {
          fitToWidth();
        }

        state.overlayIds = renderHighlightOverlays(osd, page, state.overlayIds);
      });
      osd.open(getTileSource(page));
      pageLabel.textContent = `Pagina ${page.pageNumber} de ${pages.length}`;
      updatePageInput(page);
      updateViewerLabel(page);
      updateNavState();
    }

    function startSwipe(event) {
      if (!mobileViewport || !mobileViewport.matches || event.touches.length !== 1) {
        swipeStart = null;
        return;
      }

      const touch = event.touches[0];

      swipeStart = {
        x: touch.clientX,
        y: touch.clientY,
        time: Date.now(),
      };
    }

    function endSwipe(event) {
      if (!swipeStart || !mobileViewport || !mobileViewport.matches || !event.changedTouches.length) {
        swipeStart = null;
        return;
      }

      const touch = event.changedTouches[0];
      const deltaX = touch.clientX - swipeStart.x;
      const deltaY = touch.clientY - swipeStart.y;
      const duration = Date.now() - swipeStart.time;
      const minimumDistance = Math.max(50, osdElement.clientWidth * 0.08);

      swipeStart = null;

      if (duration > 800 || Math.abs(deltaX) < minimumDistance || Math.abs(deltaX) <= Math.abs(deltaY) * 1.25) {
        return;
      }

      suppressViewerClick = true;
      goToPage(state.activeIndex + (deltaX < 0 ? 1 : -1));

      window.setTimeout(() => {
        suppressViewerClick = false;
      }, 400);
    }

    pageLinks.forEach((link) => {
      link.addEventListener('click', () => {
        const pageNumber = Number(link.dataset.manuscriptGoPage || 0);
        const pageIndex = findPageIndex(pageNumber);

        if (pageIndex >= 0) {
          goToPage(pageIndex);
          osdElement.focus();
        }
      });
    });

    if (pageJumpForm && pageInput) {
      pageJumpForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const requestedPageNumber = Number(pageInput.value || 0);
        const pageIndex = findPageIndex(requestedPageNumber);

        if (pageIndex >= 0) {
          goToPage(pageIndex);
          osdElement.focus();
          return;
        }

        updatePageInput(pages[state.activeIndex]);
      });
    }

    if (searchToggleButton) {
      searchToggleButton.addEventListener('click', () => {
        toggleSearchOverlay();
      });
    }

    if (searchCloseButton) {
      searchCloseButton.addEventListener('click', () => {
        setSearchOpen(false);
        searchToggleButton.focus();
      });
    }

    if (searchResetButton) {
      searchResetButton.addEventListener('click', () => {
        const clearUrl = new URL(
          searchResetButton.dataset.manuscriptClearUrl || window.location.href,
          window.location.href
        );

        if (state.isFullscreen) {
          updateViewerFromSearch(clearUrl);
          return;
        }

        window.location.assign(clearUrl.toString());
      });
    }

    if (searchOverlay) {
      searchOverlay.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          setSearchOpen(false);

          if (searchToggleButton) {
            searchToggleButton.focus();
          }
        }
      });

      searchOverlay.addEventListener('submit', (event) => {
        if (!state.isFullscreen) {
          setSearchOpen(false);
          return;
        }

        event.preventDefault();
        updateViewerFromSearch(getSearchRequestUrl(searchOverlay));
      });

      searchOverlay.addEventListener('click', (event) => {
        const clearLink = event.target.closest('.manuscript-single__search-clear');

        if (!clearLink || !state.isFullscreen) {
          return;
        }

        event.preventDefault();
        updateViewerFromSearch(new URL(clearLink.href, window.location.href));
      });
    }

    previousButton.addEventListener('click', () => {
      goToPage(state.activeIndex - 1);
      osdElement.focus();
    });

    nextButton.addEventListener('click', () => {
      goToPage(state.activeIndex + 1);
      osdElement.focus();
    });

    if (zoomOutButton) {
      zoomOutButton.addEventListener('click', () => {
        zoomOut();
        osdElement.focus();
      });
    }

    if (zoomInButton) {
      zoomInButton.addEventListener('click', () => {
        zoomIn();
        osdElement.focus();
      });
    }

    if (fitToggleButton) {
      fitToggleButton.addEventListener('click', () => {
        if (state.fitMode === 'page') {
          fitToWidth();
        } else {
          fitToPage();
        }
        osdElement.focus();
      });
    }

    if (fullscreenButton) {
      fullscreenButton.addEventListener('click', () => {
        toggleFullscreen();
        osdElement.focus();
      });
    }

    osdElement.addEventListener('touchstart', startSwipe, true);
    osdElement.addEventListener('touchend', endSwipe, true);
    osdElement.addEventListener('touchcancel', () => {
      swipeStart = null;
    }, true);

    osdElement.addEventListener('click', () => {
      if (suppressViewerClick) {
        suppressViewerClick = false;
        return;
      }

      if (!isViewerFullscreen()) {
        toggleFullscreen();
      }
    });

    document.addEventListener('fullscreenchange', syncFullscreenState);
    document.addEventListener('webkitfullscreenchange', syncFullscreenState);
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && state.isPseudoFullscreen) {
        setPseudoFullscreen(false);
      }
    });

    osdElement.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        goToPage(state.activeIndex - 1);
        return;
      }

      if (event.key === 'ArrowRight') {
        event.preventDefault();
        goToPage(state.activeIndex + 1);
        return;
      }

      if (event.key === '+' || event.key === '=') {
        event.preventDefault();
        zoomIn();
        return;
      }

      if (event.key === '-') {
        event.preventDefault();
        zoomOut();
        return;
      }

      if (event.key === '0') {
        event.preventDefault();
        osd.viewport.goHome();
      }
    });

    const startIndex = Math.max(
      0,
      pages.findIndex((page) => page.pageNumber === initialPageNumber)
    );

    setSearchOpen(false);
    goToPage(startIndex);
    syncFullscreenState();
  },
};
