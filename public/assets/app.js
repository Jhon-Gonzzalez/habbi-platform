/* ===== UniAlojamiento Listings Logic (Vanilla JS) ===== */
const $ = (sel, el = document) => el.querySelector(sel);
const $$ = (sel, el = document) => [...el.querySelectorAll(sel)];

const state = {
  data: [],
  filtered: [],
  page: 1,
  perPage: 9,
  view: 'grid',
  sortBy: 'featured',
  filters: {
    q: '',
    priceMin: '',
    priceMax: '',
    type: new Set(),
    guests: 1,
    minRating: '',
    amenities: new Set(),
  },
};

/* ===== Dataset REAL desde PHP ===== */
const DATA = (window.__LISTINGS__ || []).map(l => ({
  id: Number(l.id),
  title: String(l.title || ''),
  city: String(l.city || ''),
  neighborhood: String(l.neighborhood || ''),
  price: Number(l.price) || 0,
  rating: Number(l.rating || 0),         // si no tienes rating en BD, quedará 0
  reviews: Number(l.reviews || 0),
  distanceKm: Number(l.distanceKm || 0),
  type: String(l.type || ''),
  guests: Number(l.guests || 1),
  amenities: Array.isArray(l.amenities) ? l.amenities : [],
  featured: !!l.featured,
  badge: String(l.badge || ''),
  image: l.cover || '/habbi/images/Icono.png',
  price_period: String(l.price_period || 'mes')
}));

const TYPES = [...new Set(DATA.map(x => x.type).filter(Boolean))];

/* ====== Helpers ====== */
function updateYear(){ const y = $('#year'); if(y) y.textContent = new Date().getFullYear(); }

function setView(view){
  state.view = view;
  const cards = $('#cards');
  $('#viewGrid')?.classList.toggle('active', view==='grid');
  $('#viewList')?.classList.toggle('active', view==='list');
  cards?.classList.toggle('grid', view==='grid');
  cards?.classList.toggle('list', view==='list');
}

function formatPrice(n){
  try {
    return Intl.NumberFormat('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 }).format(n);
  } catch { return `$${n}`; }
}

function starSVG(){ return `<svg class="star" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .587l3.668 7.431 8.2 1.193-5.934 5.787 1.4 8.168L12 18.896l-7.334 3.87 1.4-8.168L.132 9.211l8.2-1.193z"/></svg>`; }

function ratingStars(r){
  const full = Math.floor(r), half = (r - full) >= 0.5;
  let html = '';
  for(let i=0;i<full;i++) html += starSVG();
  if(half) html += starSVG();
  return `<span class="stars" aria-label="Puntuación ${r} de 5">${html}</span>`;
}

function badgeClass(b){ if(b==='Nuevo') return 'badge badge--new'; if(b==='Oferta') return 'badge badge--deal'; return 'badge'; }

/* ====== Rendering ====== */
function renderSkeleton(n=6){
  const cards = $('#cards');
  if (!cards) return;
  cards.innerHTML = Array.from({length:n}).map(()=>`
    <div class="card">
      <div class="card__media skeleton"></div>
      <div class="card__body">
        <div class="skeleton" style="height:20px; width:60%"></div>
        <div class="skeleton" style="height:14px; width:90%"></div>
        <div class="skeleton" style="height:14px; width:70%"></div>
        <div class="skeleton" style="height:34px; width:100%"></div>
      </div>
    </div>`).join('');
}

function card(item){
  const amenities = (item.amenities || []).slice(0,4).map(a=>`<span class="amenity">${a}</span>`).join('');
  return `<article class="card" data-id="${item.id}">
    <div class="card__media">
      <img class="card__img" src="${item.image}" alt="${item.title} — ${item.city}, ${item.neighborhood}">
      ${item.badge ? `<span class="${badgeClass(item.badge)}">${item.badge}</span>` : ''}
    </div>
    <div class="card__body">
      <div class="card__title">
        <h3>${item.title}</h3>
        <div class="price">${formatPrice(item.price)}/${item.price_period}</div>
      </div>
      <div class="meta">
        <span>${item.city}${item.neighborhood ? ' • ' + item.neighborhood : ''}</span>
        ${item.distanceKm ? `<span class="distance">A ${item.distanceKm} km</span>` : ''}
      </div>
      <div class="amenities">${amenities || ''}</div>
      <div class="actions">
        ${item.rating ? `<div class="rating">${ratingStars(item.rating)} <span>${item.rating}${item.reviews ? ' ('+item.reviews+')' : ''}</span></div>` : '<span></span>'}
        <a class="btn btn-primary" href="/habbi/listings/ver.php?id=${item.id}">Ver detalles</a>
      </div>
    </div>
  </article>`;
}

function render(){
  const cardsEl = $('#cards');
  if (!cardsEl) return;

  const start = (state.page-1)*state.perPage;
  const end = start + state.perPage;
  const pageItems = state.filtered.slice(start, end);
  cardsEl.innerHTML = pageItems.map(card).join('');
  const countEl = $('#resultsCount');
  if (countEl) countEl.textContent = String(state.filtered.length);

  const totalPages = Math.max(1, Math.ceil(state.filtered.length / state.perPage));
  const pag = $('#pagination');
  if (!pag) return;
  pag.innerHTML = '';
  if(totalPages > 1){
    const btn = (p, label=p) => `<button class="page-btn ${p===state.page?'active':''}" data-page="${p}">${label}</button>`;
    pag.innerHTML = [
      btn(1, '«'),
      btn(Math.max(1, state.page-1), '‹'),
      ...Array.from({length: totalPages}).map((_,i)=>btn(i+1)),
      btn(Math.min(totalPages, state.page+1), '›'),
      btn(totalPages, '»')
    ].join('');
  }
  $$('#pagination .page-btn').forEach(b=>{
    b.addEventListener('click', e=>{
      state.page = Number(e.currentTarget.dataset.page);
      render();
      window.scrollTo({top: 0, behavior:'smooth'});
    });
  });
}

function applyFilters(){
  let result = [...state.data];

  const {q, priceMin, priceMax, guests, minRating} = state.filters;
  const types = state.filters.type;
  const amenities = state.filters.amenities;

  if(q){
    const Q = q.toLowerCase();
    result = result.filter(i =>
      (i.title||'').toLowerCase().includes(Q) ||
      (i.city||'').toLowerCase().includes(Q) ||
      (i.neighborhood||'').toLowerCase().includes(Q) ||
      (i.type||'').toLowerCase().includes(Q)
    );
  }
  if(priceMin) result = result.filter(i => i.price >= Number(priceMin));
  if(priceMax) result = result.filter(i => i.price <= Number(priceMax));
  if(types.size) result = result.filter(i => types.has(i.type));
  if(guests) result = result.filter(i => i.guests >= Number(guests));
  if(minRating) result = result.filter(i => (i.rating||0) >= Number(minRating));
  if(amenities.size) result = result.filter(i => [...amenities].every(a => (i.amenities||[]).includes(a)));

  // sort
  switch(state.sortBy){
    case 'price-asc': result.sort((a,b)=> a.price - b.price); break;
    case 'price-desc': result.sort((a,b)=> b.price - a.price); break;
    case 'rating-desc': result.sort((a,b)=> (b.rating||0) - (a.rating||0)); break;
    case 'distance-asc': result.sort((a,b)=> (a.distanceKm||0) - (b.distanceKm||0)); break;
    default:
      result.sort((a,b)=> (Number(!!b.featured) - Number(!!a.featured)) || (b.rating||0) - (a.rating||0));
  }

  state.filtered = result;
  state.page = 1;
  render();
  updateURL();
  updateMiniMap(state.filtered);
}

function updateURL(){
  const params = new URLSearchParams();
  const f = state.filters;
  if(f.q) params.set('q', f.q);
  if(f.priceMin) params.set('min', f.priceMin);
  if(f.priceMax) params.set('max', f.priceMax);
  if(f.type.size) params.set('type', [...f.type].join(','));
  if(f.guests) params.set('guests', f.guests);
  if(f.minRating) params.set('rating', f.minRating);
  if(f.amenities.size) params.set('amenities', [...f.amenities].join(','));
  params.set('sort', state.sortBy);
  params.set('view', state.view);
  history.replaceState(null, '', `${location.pathname}?${params.toString()}`);
}

function readURL(){
  const p = new URLSearchParams(location.search);
  if(p.get('q')) state.filters.q = p.get('q');
  if(p.get('min')) state.filters.priceMin = p.get('min');
  if(p.get('max')) state.filters.priceMax = p.get('max');
  if(p.get('type')) p.get('type').split(',').forEach(t => state.filters.type.add(t));
  if(p.get('guests')) state.filters.guests = Number(p.get('guests'));
  if(p.get('rating')) state.filters.minRating = p.get('rating');
  if(p.get('amenities')) p.get('amenities').split(',').forEach(a => state.filters.amenities.add(a));
  if(p.get('sort')) state.sortBy = p.get('sort');
  if(p.get('view')) setView(p.get('view'));
}

function syncControls(){
  const s = state;
  $('#q').value = s.filters.q || '';
  $('#priceMin').value = s.filters.priceMin || '';
  $('#priceMax').value = s.filters.priceMax || '';
  $('#guests').value = s.filters.guests || 1;
  $('#guestsVal').textContent = s.filters.guests || 1;
  $('#minRating').value = s.filters.minRating || '';
  $('#sortBy').value = s.sortBy;

  const wrap = $('#typeChips');
  if (wrap) {
    wrap.innerHTML = TYPES.map(t => `<button type="button" class="chip ${s.filters.type.has(t)?'active':''}" data-type="${t}">${t}</button>`).join('');
  }

  $$('.amenity').forEach(cb => { cb.checked = s.filters.amenities.has(cb.value); });
}

function bindControls(){
  $('#applyFilters')?.addEventListener('click', () => {
    state.filters.q = $('#q').value.trim();
    state.filters.priceMin = $('#priceMin').value;
    state.filters.priceMax = $('#priceMax').value;
    state.filters.guests = Number($('#guests').value);
    state.filters.minRating = $('#minRating').value;
    applyFilters();
  });

  $('#clearFilters')?.addEventListener('click', () => {
    state.filters = { q:'', priceMin:'', priceMax:'', type:new Set(), guests:1, minRating:'', amenities:new Set() };
    syncControls();
    applyFilters();
  });

  $('#guests')?.addEventListener('input', (e)=> $('#guestsVal').textContent = e.target.value);
  $('#sortBy')?.addEventListener('change', (e)=> { state.sortBy = e.target.value; applyFilters(); });

  $('#viewGrid')?.addEventListener('click', ()=> { setView('grid'); updateURL(); });
  $('#viewList')?.addEventListener('click', ()=> { setView('list'); updateURL(); });

  $('#typeChips')?.addEventListener('click', (e)=>{
    const chip = e.target.closest('.chip');
    if(!chip) return;
    const t = chip.dataset.type;
    if(state.filters.type.has(t)) state.filters.type.delete(t);
    else state.filters.type.add(t);
    chip.classList.toggle('active');
  });

  $$('.amenity').forEach(cb => {
    cb.addEventListener('change', (e) => {
      if(e.target.checked) state.filters.amenities.add(e.target.value);
      else state.filters.amenities.delete(e.target.value);
    });
  });

  $('#menuToggle')?.addEventListener('click', ()=>{
    const nav = document.querySelector('.main-nav');
    nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
  });
}

function openDetail(e){
  const id = Number(e.target.closest('[data-id]').dataset.id);
  const item = state.filtered.find(x => x.id === id) || state.data.find(x => x.id === id);
  const dlg = $('#detailModal');
  const body = $('#modalBody');
  if (!item || !dlg || !body) return;

  const amen = (item.amenities||[]).map(a=>`<span class="amenity">${a}</span>`).join('');
  body.innerHTML = `
    <div class="modal__grid">
      <div class="modal__media"><img src="${item.image}" alt="${item.title} — ${item.city}, ${item.neighborhood}" /></div>
      <div class="modal__body">
        <h3>${item.title}</h3>
        <div class="meta">${item.city} • ${item.neighborhood} • ${item.type} • Hasta ${item.guests} huésped(es)</div>
        ${item.rating ? `<div class="rating" style="margin:6px 0">${ratingStars(item.rating)} <span>${item.rating}${item.reviews ? ' ('+item.reviews+' reseñas)' : ''}</span></div>` : ''}
        <div class="modal__amenities">${amen}</div>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:12px">
          <div class="price" style="font-size:24px">${formatPrice(item.price)}/${item.price_period}</div>
          <a class="btn btn-primary" href="/habbi/listings/ver.php?id=${item.id}">Ver detalle</a>
        </div>
      </div>
    </div>`;
  $('#modalClose').onclick = ()=> dlg.close();
  dlg.showModal();
}

/* ====== Mini-mapa (Leaflet) ====== */
const CITY_COORDS = {
  "Medellín": {lat: 6.2442, lng: -75.5812},
  "Bogotá": {lat: 4.7110, lng: -74.0721},
  "Cali": {lat: 3.4516, lng: -76.5320},
  "Barranquilla": {lat: 10.9685, lng: -74.7813},
  "Bucaramanga": {lat: 7.1193, lng: -73.1227},
  "Manizales": {lat: 5.0703, lng: -75.5138},
  "Pereira": {lat: 4.8143, lng: -75.6946}
};

let miniMap, miniLayer, miniMarkers = [];

function ensureMiniMap(){
  if(miniMap) return;
  const el = document.getElementById('mapMini');
  if(!el || !window.L) return;
  miniMap = L.map('mapMini', { zoomControl:false, attributionControl:false });
  miniLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(miniMap);
  miniMap.setView([4.6, -74.1], 5);
}

function updateMiniMap(items){
  ensureMiniMap();
  if(!miniMap) return;
  miniMarkers.forEach(m => m.remove());
  miniMarkers = [];
  const bounds = [];
  items.forEach(i=>{
    const c = CITY_COORDS[i.city];
    if(!c) return;
    const m = L.marker([c.lat, c.lng]).addTo(miniMap);
    m.bindPopup(`<strong>${i.title}</strong><br>${i.city}${i.neighborhood ? ' • '+i.neighborhood : ''}<br>${formatPrice(i.price)}/${i.price_period}`);
    miniMarkers.push(m);
    bounds.push([c.lat, c.lng]);
  });
  if(bounds.length){ miniMap.fitBounds(bounds, {padding:[20,20]}); }
  else{ miniMap.setView([4.6, -74.1], 5); }
}

/* ====== Init ====== */
function init(){
  updateYear();
  renderSkeleton(6);

  state.data = DATA;            // << datos reales del backend
  readURL();
  syncControls();
  bindControls();

  applyFilters();               // render + url + mapa
  ensureMiniMap();
  updateMiniMap(state.filtered);

  // abrir modal detalle desde tarjetas
  $('#cards')?.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-id] .btn');
    if (btn) openDetail(e);
  });
}

document.addEventListener('DOMContentLoaded', init);
window.addEventListener('load', ensureMiniMap);
