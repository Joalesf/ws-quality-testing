const form = document.querySelector('#search-form');
const queryInput = document.querySelector('#query');
const categoryInput = document.querySelector('#category');
const protocolInput = document.querySelector('#protocol');
const grid = document.querySelector('#service-grid');
const countLabel = document.querySelector('#result-count');
const loading = document.querySelector('#loading');
const emptyState = document.querySelector('#empty-state');
let debounceTimer;
let currentRequest;

const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
}[char]));

function renderServices(services) {
  grid.innerHTML = services.map((service) => `
    <article class="service-card">
      <div class="card-top"><span class="protocol ${escapeHtml(service.protocol.toLowerCase())}">${escapeHtml(service.protocol)}</span><span>${escapeHtml(service.category)}</span></div>
      <h3>${escapeHtml(service.name)}</h3>
      <p>${escapeHtml(service.description)}</p>
      <code>${escapeHtml(service.http_method)} ${escapeHtml(service.endpoint)}</code>
      <div class="card-actions"><a href="${escapeHtml(service.documentation_url)}" target="_blank" rel="noopener">Documentación</a><a href="api/services.php?id=${Number(service.id)}" target="_blank" rel="noopener">Ver JSON</a></div>
    </article>`).join('');
  countLabel.textContent = `${services.length} servicio(s)`;
  emptyState.hidden = services.length > 0;
}

async function search({ log = false } = {}) {
  if (currentRequest) currentRequest.abort();
  currentRequest = new AbortController();
  const params = new URLSearchParams({
    q: queryInput.value.trim(),
    category: categoryInput.value,
    protocol: protocolInput.value,
    log: String(log)
  });
  loading.hidden = false;
  try {
    const response = await fetch(`api/search.php?${params}`, { signal: currentRequest.signal });
    const payload = await response.json();
    if (!response.ok || !payload.ok) throw new Error(payload.message || 'Error de consulta');
    renderServices(payload.data);
  } catch (error) {
    if (error.name !== 'AbortError') {
      grid.innerHTML = `<div class="alert error">${escapeHtml(error.message)}</div>`;
      countLabel.textContent = 'Error';
    }
  } finally {
    loading.hidden = true;
  }
}

form?.addEventListener('submit', (event) => {
  event.preventDefault();
  search({ log: true });
});

queryInput?.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => search(), 280);
});

[categoryInput, protocolInput].forEach((element) => element?.addEventListener('change', () => search()));

document.querySelectorAll('[data-query]').forEach((button) => {
  button.addEventListener('click', () => {
    queryInput.value = button.dataset.query || '';
    queryInput.focus();
    search({ log: true });
  });
});
