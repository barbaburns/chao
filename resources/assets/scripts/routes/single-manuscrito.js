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
    const fitPageButton = viewerElement.querySelector('[data-manuscript-fit-page]');
    const zoomActualButton = viewerElement.querySelector('[data-manuscript-zoom-actual]');
    const fitWidthButton = viewerElement.querySelector('[data-manuscript-fit-width]');
    const fullscreenButton = viewerElement.querySelector('[data-manuscript-fullscreen]');
    const pageLinks = Array.from(document.querySelectorAll('[data-manuscript-go-page]'));
    const previousButton = viewerElement.querySelector('[data-manuscript-prev]');
    const nextButton = viewerElement.querySelector('[data-manuscript-next]');
    const pages = getPages(pagesScript);
    const initialPageNumber = Number(viewerElement.dataset.initialPageNumber || 0);
    const state = {
      activeIndex: 0,
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
      minZoomLevel: 0.5,
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

    function getActiveTiledImage() {
      if (!osd.world || typeof osd.world.getItemAt !== 'function' || osd.world.getItemCount() <= 0) {
        return null;
      }

      return osd.world.getItemAt(0);
    }

    function zoomToActualSize() {
      const tiledImage = getActiveTiledImage();

      if (!tiledImage || !osd.viewport) {
        return;
      }

      osd.viewport.zoomTo(tiledImage.imageToViewportZoom(1), null, true);
      osd.viewport.applyConstraints();
    }

    function fitToPage() {
      if (!osd.viewport || typeof osd.viewport.goHome !== 'function') {
        return;
      }

      osd.viewport.goHome(true);
      osd.viewport.applyConstraints();
    }

    function fitToWidth() {
      if (!osd.viewport || typeof osd.viewport.fitHorizontally !== 'function') {
        return;
      }

      osd.viewport.fitHorizontally(true);
      osd.viewport.applyConstraints();
    }

    function updateFullscreenButton(isFullscreen) {
      if (!fullscreenButton) {
        return;
      }

      fullscreenButton.textContent = isFullscreen ? '⤡' : '⤢';
      fullscreenButton.setAttribute('aria-label', isFullscreen ? 'Sair da tela cheia' : 'Tela cheia');
      fullscreenButton.setAttribute('data-manuscript-tooltip', isFullscreen ? 'Sair da tela cheia' : 'Tela cheia');
    }

    function syncFullscreenState() {
      const isFullscreen = document.fullscreenElement === viewerElement;

      viewerElement.classList.toggle('is-fullscreen', isFullscreen);
      updateFullscreenButton(isFullscreen);
    }

    function toggleFullscreen() {
      if (document.fullscreenElement === viewerElement) {
        if (typeof document.exitFullscreen === 'function') {
          document.exitFullscreen();
        }

        return;
      }

      if (typeof viewerElement.requestFullscreen === 'function') {
        viewerElement.requestFullscreen();
      }
    }

    function goToPage(index) {
      const boundedIndex = Math.min(Math.max(index, 0), pages.length - 1);
      const page = pages[boundedIndex];

      state.activeIndex = boundedIndex;
      state.overlayIds = clearOverlays(osd, state.overlayIds);
      osd.addOnceHandler('open', () => {
        state.overlayIds = renderHighlightOverlays(osd, page, state.overlayIds);
      });
      osd.open(getTileSource(page));
      pageLabel.textContent = `Pagina ${page.pageNumber} de ${pages.length}`;
      updatePageInput(page);
      updateViewerLabel(page);
      updateNavState();
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

    previousButton.addEventListener('click', () => {
      goToPage(state.activeIndex - 1);
      osdElement.focus();
    });

    nextButton.addEventListener('click', () => {
      goToPage(state.activeIndex + 1);
      osdElement.focus();
    });

    if (fitPageButton) {
      fitPageButton.addEventListener('click', () => {
        fitToPage();
        osdElement.focus();
      });
    }

    if (zoomActualButton) {
      zoomActualButton.addEventListener('click', () => {
        zoomToActualSize();
        osdElement.focus();
      });
    }

    if (fitWidthButton) {
      fitWidthButton.addEventListener('click', () => {
        fitToWidth();
        osdElement.focus();
      });
    }

    if (fullscreenButton) {
      fullscreenButton.addEventListener('click', () => {
        toggleFullscreen();
        osdElement.focus();
      });
    }

    document.addEventListener('fullscreenchange', syncFullscreenState);

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
        osd.viewport.zoomBy(1.2);
        osd.viewport.applyConstraints();
        return;
      }

      if (event.key === '-') {
        event.preventDefault();
        osd.viewport.zoomBy(1 / 1.2);
        osd.viewport.applyConstraints();
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

    goToPage(startIndex);
    syncFullscreenState();
  },
};
