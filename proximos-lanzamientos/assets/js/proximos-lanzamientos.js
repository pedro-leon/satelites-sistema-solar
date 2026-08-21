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
      const parsedUrl = new URL(url);
      return parsedUrl.protocol === "https:" || parsedUrl.protocol === "http:";
    } catch (error) {
      return false;
    }
  }

  function getLaunchLinks(launch) {
    const videoLinks = launch.vidURLs || launch.vid_urls || [];
    const webLinks = launch.infoURLs || launch.info_urls || [];
    const seen = new Set();

    return [
      ...videoLinks.map((link) => ({ ...link, label: config.videoLabel || "Video", className: "video" })),
      ...webLinks.map((link) => ({
        ...link,
        label: link.type?.name?.toLowerCase().includes("official")
          ? (config.officialWebLabel || "Web oficial")
          : (config.webLabel || "Web"),
        className: "web"
      }))
    ].filter((link) => {
      if (!link.url || !isSafeUrl(link.url) || seen.has(link.url)) return false;
      seen.add(link.url);
      return true;
    });
  }

  function renderLaunchLinks(links) {
    if (!links.length) return "";

    const markup = links.slice(0, 4).map((link) => `
      <a class="ple-launch-link ${escapeHtml(link.className)}" href="${encodeURI(link.url)}" target="_blank" rel="noreferrer">
        ${escapeHtml(link.label)}
      </a>
    `).join("");

    return `<div class="ple-launch-links" aria-label="Enlaces del lanzamiento">${markup}</div>`;
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

  function getLaunchImage(launch) {
    const url = launch.image?.image_url || launch.image || launch.rocket?.configuration?.image_url || "";
    return isSafeUrl(url) ? url : "";
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

  function createRenderer(root) {
    const launchesEl = root.querySelector("[data-ple-launches-list]");
    const statusEl = root.querySelector("[data-ple-status]");
    const timeNoticeEl = root.querySelector(".ple-time-notice");
    const launchCountEl = root.querySelector("[data-ple-launch-count]");
    const nextCountdownEl = root.querySelector("[data-ple-next-countdown]");
    const formatters = createDateFormatters();

    if (timeNoticeEl) {
      timeNoticeEl.textContent = config.localTimeNotice || "Todas las horas se muestran en tu hora local.";
    }

    function renderLaunches(launches) {
      const nextFutureLaunch = getNextFutureLaunch(launches);

      launchesEl.innerHTML = "";
      launchCountEl.textContent = launches.length;
      nextCountdownEl.textContent = nextFutureLaunch ? getCountdownLabel(nextFutureLaunch.window_start) : "--";

      if (!launches.length) {
        launchesEl.innerHTML = `<div class="ple-empty">${escapeHtml(config.emptyMessage || "No hay lanzamientos próximos disponibles ahora mismo.")}</div>`;
        return;
      }

      const fragment = document.createDocumentFragment();

      launches.forEach((launch) => {
        const card = document.createElement("article");
        const imageUrl = getLaunchImage(launch);
        const launchDate = launch.window_start ? new Date(launch.window_start) : null;
        const launchDateMarkup = launchDate
          ? `<span class="ple-date-line">${escapeHtml(formatters.local.format(launchDate))}</span><span class="ple-date-line">${escapeHtml(formatters.utc.format(launchDate))} UTC</span>`
          : escapeHtml(config.pendingDate || "Fecha pendiente");
        const missionDescription = launch.mission?.description || launch.launch_service_provider?.description;
        const statusClass = getStatusClass(launch.status?.abbrev || launch.status?.name);
        const launchLinks = getLaunchLinks(launch);

        card.className = "ple-launch-card";
        card.innerHTML = `
          ${imageUrl ? `<img class="ple-launch-image" src="${encodeURI(imageUrl)}" alt="">` : ""}
          <div class="ple-mission">
            <h3 class="ple-mission-title">${safeText(launch.name)}</h3>
            <span class="ple-badge ${statusClass}">${safeText(launch.status?.abbrev, "TBD")}</span>
          </div>
          <dl class="ple-meta">
            <div class="ple-meta-row">
              <dt>${escapeHtml(config.dateLabel || "Fecha y hora")}</dt>
              <dd class="ple-date-list">${launchDateMarkup}</dd>
            </div>
            <div class="ple-meta-row">
              <dt>${escapeHtml(config.agencyLabel || "Agencia")}</dt>
              <dd>${safeText(launch.launch_service_provider?.name)}</dd>
            </div>
            <div class="ple-meta-row">
              <dt>${escapeHtml(config.rocketLabel || "Cohete")}</dt>
              <dd>${safeText(launch.rocket?.configuration?.full_name || launch.rocket?.configuration?.name)}</dd>
            </div>
            <div class="ple-meta-row">
              <dt>${escapeHtml(config.padLabel || "Plataforma")}</dt>
              <dd>${safeText(launch.pad?.name)} · ${safeText(launch.pad?.location?.name, config.pendingLocation || "Ubicación pendiente")}</dd>
            </div>
          </dl>
          <p class="ple-description">${safeText(missionDescription, config.missionFallback || "La misión todavía no tiene descripción pública.")}</p>
          ${renderLaunchLinks(launchLinks)}
        `;

        fragment.appendChild(card);
      });

      launchesEl.appendChild(fragment);
    }

    async function loadLaunches() {
      statusEl.textContent = config.statusLoading || "Cargando próximos lanzamientos...";
      statusEl.classList.remove("is-error");

      try {
        const response = await fetch(config.apiUrl);

        if (!response.ok) {
          throw new Error(`Launch API status ${response.status}`);
        }

        const data = await response.json();
        renderLaunches(data.results || []);
        statusEl.textContent = `${config.statusUpdated || "Actualizado:"} ${new Date().toLocaleTimeString(document.documentElement.lang || "es-ES", {
          hour: "2-digit",
          minute: "2-digit"
        })}`;
      } catch (error) {
        launchesEl.innerHTML = "";
        launchCountEl.textContent = "--";
        nextCountdownEl.textContent = "--";
        statusEl.textContent = config.statusError || "No se pudieron cargar los lanzamientos. Revisa la conexión o inténtalo más tarde.";
        statusEl.classList.add("is-error");
        console.error(error);
      }
    }

    loadLaunches();
    window.setInterval(loadLaunches, Number(config.refreshInterval) || 600000);
  }

  document.querySelectorAll("[data-ple-launches]").forEach(createRenderer);
})();
