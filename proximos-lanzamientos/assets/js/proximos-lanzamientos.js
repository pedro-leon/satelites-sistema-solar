(function () {
  const config = window.pleLaunchesConfig || {};

  function cleanText(value, fallback = config.fallbackText || "Pendiente de confirmar") {
    return value && String(value).trim() ? value : fallback;
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function safeText(value, fallback) {
    return escapeHtml(cleanText(value, fallback));
  }

  function getStatusClass(status) {
    return cleanText(status, "tbd").toLowerCase().replace(/[^a-z0-9]+/g, "-");
  }

  function isSafeUrl(url) {
    try {
      const parsedUrl = new URL(url, window.location.href);
      return parsedUrl.protocol === "https:" || parsedUrl.protocol === "http:";
    } catch (error) {
      return false;
    }
  }

  function getCountdownLabel(dateValue) {
    const launchTime = new Date(dateValue).getTime();
    const diffMs = launchTime - Date.now();

    if (!Number.isFinite(launchTime)) return "--";
    if (diffMs <= 0) return "en curso";

    const days = Math.floor(diffMs / 86400000);
    const hours = Math.floor((diffMs % 86400000) / 3600000);
    const minutes = Math.floor((diffMs % 3600000) / 60000);

    if (days > 0) return `${days} d ${hours} h`;
    if (hours > 0) return `${hours} h ${minutes} min`;
    return `${minutes} min`;
  }

  function getNextFutureLaunch(launches) {
    const now = Date.now();

    return launches.find((launch) => {
      const launchTime = new Date(launch.window_start).getTime();
      return Number.isFinite(launchTime) && launchTime > now;
    }) || null;
  }

  function createDateFormatters() {
    const language = document.documentElement.lang || "es-ES";

    return {
      local: new Intl.DateTimeFormat(language, {
        dateStyle: "full",
        timeStyle: "short"
      }),
      utc: new Intl.DateTimeFormat(language, {
        dateStyle: "full",
        timeStyle: "short",
        timeZone: "UTC"
      })
    };
  }

  function renderLinks(links) {
    const safeLinks = (links || []).filter((link) => isSafeUrl(link.url)).slice(0, 4);
    if (!safeLinks.length) return "";

    const labels = {
      video: config.videoLabel || "Video",
      official: config.officialWebLabel || "Web oficial",
      web: config.webLabel || "Web"
    };

    const markup = safeLinks.map((link) => {
      const className = "video" === link.type ? "video" : "web";
      const label = labels[link.type] || labels.web;
      return `<a class="ple-launch-link ${className}" href="${encodeURI(link.url)}" target="_blank" rel="noreferrer noopener">${escapeHtml(label)}</a>`;
    }).join("");

    return `<div class="ple-launch-links" aria-label="Enlaces del lanzamiento">${markup}</div>`;
  }

  function renderCard(launch, formatters) {
    const imageUrl = isSafeUrl(launch.image) ? launch.image : "";
    const launchDate = launch.window_start ? new Date(launch.window_start) : null;
    const isValidDate = launchDate && Number.isFinite(launchDate.getTime());
    const iso = isValidDate ? launchDate.toISOString() : "";
    const dateMarkup = isValidDate
      ? `<time class="ple-date-line" datetime="${iso}">${escapeHtml(formatters.local.format(launchDate))}</time><time class="ple-date-line" datetime="${iso}">${escapeHtml(formatters.utc.format(launchDate))} UTC</time>`
      : escapeHtml(config.pendingDate || "Fecha pendiente");
    const statusClass = getStatusClass(launch.status_abbrev || launch.status_name);

    return `
      <article class="ple-launch-card">
        ${imageUrl ? `<img class="ple-launch-image" src="${encodeURI(imageUrl)}" alt="" loading="lazy" decoding="async">` : ""}
        <div class="ple-mission">
          <h3 class="ple-mission-title">${safeText(launch.name)}</h3>
          <span class="ple-badge ${statusClass}">${safeText(launch.status_abbrev, "TBD")}</span>
        </div>
        <dl class="ple-meta">
          <div class="ple-meta-row">
            <dt>${escapeHtml(config.dateLabel || "Fecha y hora")}</dt>
            <dd class="ple-date-list">${dateMarkup}</dd>
          </div>
          <div class="ple-meta-row">
            <dt>${escapeHtml(config.agencyLabel || "Agencia")}</dt>
            <dd>${safeText(launch.agency)}</dd>
          </div>
          <div class="ple-meta-row">
            <dt>${escapeHtml(config.rocketLabel || "Cohete")}</dt>
            <dd>${safeText(launch.rocket)}</dd>
          </div>
          <div class="ple-meta-row">
            <dt>${escapeHtml(config.padLabel || "Plataforma")}</dt>
            <dd>${safeText(launch.pad)} · ${safeText(launch.location, config.pendingLocation || "Ubicación pendiente")}</dd>
          </div>
        </dl>
        <p class="ple-description">${safeText(launch.description, config.missionFallback || "La misión todavía no tiene descripción pública.")}</p>
        ${renderLinks(launch.links)}
      </article>
    `;
  }

  function getParts(root) {
    return {
      root,
      launchesEl: root.querySelector("[data-ple-launches-list]"),
      statusEl: root.querySelector("[data-ple-status]"),
      timeNoticeEl: root.querySelector(".ple-time-notice"),
      launchCountEl: root.querySelector("[data-ple-launch-count]"),
      nextCountdownEl: root.querySelector("[data-ple-next-countdown]")
    };
  }

  function main() {
    const roots = Array.from(document.querySelectorAll("[data-ple-launches]"));
    if (!roots.length) return;

    // Todas las instancias del shortcode en la página comparten un único
    // fetch/temporizador: evita duplicar peticiones si el shortcode se usa
    // más de una vez en la misma página.
    const parts = roots.map(getParts);
    const formatters = createDateFormatters();
    let abortController = null;

    parts.forEach((part) => {
      if (part.timeNoticeEl) {
        part.timeNoticeEl.textContent = config.localTimeNotice || "Todas las horas se muestran en tu hora local.";
      }
    });

    function renderAll(launches) {
      const nextFutureLaunch = getNextFutureLaunch(launches);
      const countdownLabel = nextFutureLaunch ? getCountdownLabel(nextFutureLaunch.window_start) : "--";
      const emptyMarkup = `<div class="ple-empty">${escapeHtml(config.emptyMessage || "No hay lanzamientos próximos disponibles ahora mismo.")}</div>`;
      const cardsMarkup = launches.length ? launches.map((launch) => renderCard(launch, formatters)).join("") : emptyMarkup;

      parts.forEach((part) => {
        part.launchesEl.innerHTML = cardsMarkup;
        part.launchCountEl.textContent = launches.length;
        part.nextCountdownEl.textContent = countdownLabel;
      });
    }

    async function loadLaunches() {
      parts.forEach((part) => {
        part.statusEl.textContent = config.statusLoading || "Cargando próximos lanzamientos...";
        part.statusEl.classList.remove("is-error");
      });

      if (abortController) abortController.abort();
      abortController = new AbortController();

      try {
        const response = await fetch(config.apiUrl, { signal: abortController.signal });

        if (!response.ok) {
          throw new Error(`Launch API status ${response.status}`);
        }

        const data = await response.json();
        renderAll(data.launches || []);

        const timeLabel = new Date().toLocaleTimeString(document.documentElement.lang || "es-ES", {
          hour: "2-digit",
          minute: "2-digit"
        });
        parts.forEach((part) => {
          part.statusEl.textContent = `${config.statusUpdated || "Actualizado:"} ${timeLabel}`;
        });
      } catch (error) {
        if ("AbortError" === error.name) return;

        parts.forEach((part) => {
          part.launchesEl.innerHTML = "";
          part.launchCountEl.textContent = "--";
          part.nextCountdownEl.textContent = "--";
          part.statusEl.textContent = config.statusError || "No se pudieron cargar los lanzamientos. Revisa la conexión o inténtalo más tarde.";
          part.statusEl.classList.add("is-error");
        });
        console.error(error);
      }
    }

    loadLaunches();
    window.setInterval(loadLaunches, Number(config.refreshInterval) || 900000);
  }

  main();
})();
