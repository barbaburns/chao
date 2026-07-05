import OpenSeadragon from 'openseadragon';

function setButtonState(buttons, activePageNumber) {
  buttons.forEach((button) => {
    const isActive = Number(button.dataset.pageNumber) === activePageNumber;

    button.classList.toggle('is-active', isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
}

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
    const pageButtons = Array.from(viewerElement.querySelectorAll('[data-manuscript-page-button]'));
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
      setButtonState(pageButtons, page.pageNumber);
      updateViewerLabel(page);
      updateNavState();
    }

    pageButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const pageNumber = Number(button.dataset.pageNumber || 0);
        const pageIndex = pages.findIndex((page) => page.pageNumber === pageNumber);

        if (pageIndex >= 0) {
          goToPage(pageIndex);
          osdElement.focus();
        }
      });
    });

    pageLinks.forEach((link) => {
      link.addEventListener('click', () => {
        const pageNumber = Number(link.dataset.manuscriptGoPage || 0);
        const pageIndex = pages.findIndex((page) => page.pageNumber === pageNumber);

        if (pageIndex >= 0) {
          goToPage(pageIndex);
          osdElement.focus();
        }
      });
    });

    previousButton.addEventListener('click', () => {
      goToPage(state.activeIndex - 1);
      osdElement.focus();
    });

    nextButton.addEventListener('click', () => {
      goToPage(state.activeIndex + 1);
      osdElement.focus();
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
  },
};
