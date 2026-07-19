((): void => {
  const mediaTriggers = Array.from(document.querySelectorAll<HTMLElement>('[data-media-open]'));
  if (mediaTriggers.length === 0) {
    return;
  }

  const overlay = document.querySelector<HTMLElement>('[data-media-overlay]');
  const title = document.querySelector<HTMLElement>('[data-media-title]');
  const image = document.querySelector<HTMLImageElement>('[data-media-image]');
  const video = document.querySelector<HTMLVideoElement>('[data-media-video]');
  const videoSource = video?.querySelector<HTMLSourceElement>('source') ?? null;
  const closeButtons = Array.from(document.querySelectorAll<HTMLElement>('[data-media-close]'));

  if (!overlay || !title || !image || !video || !videoSource) {
    return;
  }

  const closeOverlay = (): void => {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';

    image.hidden = true;
    image.src = '';
    image.alt = '';

    video.pause();
    video.hidden = true;
    videoSource.src = '';
    video.load();

    title.textContent = '';
  };

  const openOverlay = (type: string, src: string, mediaTitle: string): void => {
    title.textContent = mediaTitle;

    if (type === 'video') {
      image.hidden = true;
      image.src = '';

      videoSource.src = src;
      video.hidden = false;
      video.load();
    } else {
      video.pause();
      video.hidden = true;
      videoSource.src = '';
      video.load();

      image.src = src;
      image.alt = mediaTitle;
      image.hidden = false;
    }

    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  mediaTriggers.forEach((trigger) => {
    trigger.addEventListener('click', (event: Event) => {
      event.preventDefault();
      const type = trigger.dataset.mediaType ?? 'image';
      const src = trigger.dataset.mediaSrc ?? '';
      const mediaTitle = trigger.dataset.mediaTitle ?? 'Galerie';

      if (src !== '') {
        openOverlay(type, src, mediaTitle);
      }
    });
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeOverlay);
  });

  document.addEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
      closeOverlay();
    }
  });
})();
