<div id="page-loading-overlay" class="page-loading-overlay" role="status" aria-live="polite" aria-label="Chargement en cours">
  <div class="page-loading-overlay__content">
    <div class="spinner-border text-primary" role="presentation"></div>
    <span class="page-loading-overlay__text">Chargement…</span>
  </div>
</div>

<style>
  .page-loading-overlay {
    position: fixed;
    inset: 0;
    z-index: 11000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    transition: opacity 0.2s ease, visibility 0.2s ease;
  }

  .page-loading-overlay.is-hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
  }

  .page-loading-overlay__content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.85);
    box-shadow: 0 8px 32px rgba(15, 23, 42, 0.08);
  }

  .page-loading-overlay__text {
    font-size: 0.875rem;
    color: #566a7f;
    font-weight: 500;
  }
</style>

<script>
  (function () {
    var overlay = document.getElementById('page-loading-overlay');
    if (!overlay) return;

    function showLoading() {
      overlay.classList.remove('is-hidden');
    }

    function hideLoading() {
      overlay.classList.add('is-hidden');
    }

    if (document.readyState === 'complete') {
      hideLoading();
    } else {
      window.addEventListener('load', hideLoading);
    }

    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        hideLoading();
      }
    });

    document.addEventListener('click', function (event) {
      var link = event.target.closest('a[href]');
      if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

      var href = link.getAttribute('href');
      if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;

      try {
        var url = new URL(link.href, window.location.origin);
        if (url.origin === window.location.origin) {
          showLoading();
        }
      } catch (e) {}
    });

    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!(form instanceof HTMLFormElement) || form.target === '_blank') return;

      // Attendre les handlers (ex. preventDefault pour ouvrir un 2e modal)
      // avant d’afficher l’overlay, sinon le chargement reste bloqué.
      setTimeout(function () {
        if (event.defaultPrevented) {
          hideLoading();
          return;
        }
        showLoading();
      }, 0);
    }, true);
  })();
</script>
