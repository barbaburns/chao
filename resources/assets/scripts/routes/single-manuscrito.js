function setButtonState(buttons, activePageNumber) {
  buttons.forEach((button) => {
    const isActive = Number(button.dataset.pageNumber) === activePageNumber;

    button.classList.toggle('is-active', isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
}

function setPanelState(panels, activePageNumber) {
  panels.forEach((panel) => {
    panel.classList.toggle('is-active', Number(panel.dataset.pageNumber) === activePageNumber);
  });
}

export default {
  init() {
    const viewer = document.querySelector('[data-manuscript-viewer]');

    if (!viewer) {
      return;
    }

    const frame = viewer.querySelector('[data-manuscript-frame]');
    const image = viewer.querySelector('[data-manuscript-image]');
    const canvas = viewer.querySelector('[data-manuscript-canvas]');
    const overlay = viewer.querySelector('[data-manuscript-overlay]');
    const pageLabel = viewer.querySelector('[data-manuscript-page-label]');
    const highlightLabel = viewer.querySelector('[data-manuscript-highlight-label]');
    const pagesScript = viewer.querySelector('[data-manuscript-pages]');
    const pageButtons = Array.from(viewer.querySelectorAll('[data-manuscript-page-button]'));
    const pagePanels = Array.from(document.querySelectorAll('[data-manuscript-page-panel]'));
    const pageLinks = Array.from(document.querySelectorAll('[data-manuscript-go-page]'));
    const previousButton = viewer.querySelector('[data-manuscript-prev]');
    const nextButton = viewer.querySelector('[data-manuscript-next]');
    const scaleModeButtons = Array.from(viewer.querySelectorAll('[data-manuscript-scale-mode]'));
    const zoomInButton = viewer.querySelector('[data-manuscript-zoom-in]');
    const zoomOutButton = viewer.querySelector('[data-manuscript-zoom-out]');
    const initialPageNumber = Number(viewer.dataset.initialPageNumber || 0);
    const pages = pagesScript ? JSON.parse(pagesScript.textContent || '[]') : [];
    const state = {
      activeIndex: 0,
      scaleMode: 'fit-width',
      manualScale: 1,
    };

    if (!frame || !image || !canvas || !overlay || !pages.length) {
      return;
    }

    function getScaleForMode(mode) {
      if (!image.naturalWidth || !image.naturalHeight) {
        return 1;
      }

      if (mode === 'fit-width') {
        return frame.clientWidth / image.naturalWidth;
      }

      if (mode === 'fit-height') {
        return frame.clientHeight / image.naturalHeight;
      }

      if (mode === 'manual') {
        return state.manualScale;
      }

      return 1;
    }

    function applyScale() {
      if (!image.naturalWidth || !image.naturalHeight) {
        return;
      }

      const scale = Math.max(getScaleForMode(state.scaleMode), 0.1);

      canvas.style.width = `${image.naturalWidth}px`;
      canvas.style.height = `${image.naturalHeight}px`;
      image.style.width = `${image.naturalWidth}px`;
      image.style.height = `${image.naturalHeight}px`;
      canvas.style.transform = `scale(${scale})`;
    }

    function renderHighlights() {
      const page = pages[state.activeIndex];
      const boxes = Array.isArray(page.highlightBoxes) ? page.highlightBoxes : [];
      const approximateCount = boxes.filter((box) => box && box.approximate).length;
      const exactCount = boxes.length - approximateCount;

      overlay.innerHTML = '';
      highlightLabel.textContent = boxes.length
        ? approximateCount > 0 && exactCount === 0
          ? `${boxes.length} destaque(s) aproximado(s) nesta pagina`
          : approximateCount > 0
            ? `${boxes.length} destaque(s) nesta pagina (${approximateCount} aproximado(s))`
            : `${boxes.length} destaque(s) visual(is) nesta pagina`
        : 'Sem destaque visual para a busca atual nesta pagina';

      boxes.forEach((box) => {
        const element = document.createElement('span');
        element.className = 'manuscript-single__viewer-highlight';

        if (box.approximate) {
          element.classList.add('manuscript-single__viewer-highlight--approximate');
        }

        element.style.left = `${box.x}px`;
        element.style.top = `${box.y}px`;
        element.style.width = `${box.width}px`;
        element.style.height = `${box.height}px`;
        element.setAttribute('title', box.text || '');
        overlay.appendChild(element);
      });
    }

    function setScaleMode(mode) {
      state.scaleMode = mode;

      scaleModeButtons.forEach((button) => {
        const isActive = button.dataset.manuscriptScaleMode === mode;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      applyScale();
    }

    function updateNavState() {
      previousButton.disabled = state.activeIndex <= 0;
      nextButton.disabled = state.activeIndex >= pages.length - 1;
    }

    function setActivePage(index) {
      const boundedIndex = Math.min(Math.max(index, 0), pages.length - 1);
      const page = pages[boundedIndex];

      state.activeIndex = boundedIndex;
      image.src = page.imageUrl;
      image.alt = page.imageAlt;
      overlay.innerHTML = '';

      pageLabel.textContent = `Pagina ${page.pageNumber} de ${pages.length}`;
      setButtonState(pageButtons, page.pageNumber);
      setPanelState(pagePanels, page.pageNumber);
      updateNavState();
      frame.scrollTop = 0;
      frame.scrollLeft = 0;

      if (image.complete) {
        applyScale();
        renderHighlights();
      }
    }

    pageButtons.forEach((button, index) => {
      button.addEventListener('click', () => {
        setActivePage(index);
      });
    });

    pageLinks.forEach((link) => {
      link.addEventListener('click', () => {
        const pageNumber = Number(link.dataset.manuscriptGoPage || 0);
        const pageIndex = pages.findIndex((page) => page.pageNumber === pageNumber);

        if (pageIndex >= 0) {
          setActivePage(pageIndex);
        }
      });
    });

    previousButton.addEventListener('click', () => {
      setActivePage(state.activeIndex - 1);
    });

    nextButton.addEventListener('click', () => {
      setActivePage(state.activeIndex + 1);
    });

    scaleModeButtons.forEach((button) => {
      button.addEventListener('click', () => {
        setScaleMode(button.dataset.manuscriptScaleMode);
      });
    });

    zoomInButton.addEventListener('click', () => {
      const baseScale = getScaleForMode(state.scaleMode);
      state.manualScale = baseScale * 1.2;
      setScaleMode('manual');
    });

    zoomOutButton.addEventListener('click', () => {
      const baseScale = getScaleForMode(state.scaleMode);
      state.manualScale = baseScale / 1.2;
      setScaleMode('manual');
    });

    image.addEventListener('load', () => {
      applyScale();
      renderHighlights();
    });
    window.addEventListener('resize', applyScale);

    const startIndex = Math.max(
      0,
      pages.findIndex((page) => page.pageNumber === initialPageNumber)
    );

    setActivePage(startIndex);
    setScaleMode('fit-width');
  },
};
