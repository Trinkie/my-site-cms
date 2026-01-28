// ===== Helpers =====
function setFilesList(target, files) {
  if (!target) return;
  target.innerHTML = '';
  if (!files || files.length === 0) return;
  [...files].forEach(f => {
    const div = document.createElement('div');
    div.className = 'item';
    div.textContent = `${f.name} (${Math.round(f.size / 1024)} KB)`;
    target.appendChild(div);
  });
}

// ===== Form + Filament select =====
(function initForm() {
  const form = document.getElementById('requestForm');
  if (!form) return;

  const attachmentsHint = document.getElementById('attachmentsHint');
  const attachmentsImagesBlock = document.getElementById('attachmentsImagesBlock');
  const attachmentsImages = document.getElementById('attachmentsImages');

  const modelFileBlock = document.getElementById('modelFileBlock');
  const modelFile = document.getElementById('modelFile');

  const materialsBlock = document.getElementById('materialsBlock');
  const strengthBlock = document.getElementById('strengthBlock');

  const imagesList = document.getElementById('imagesList');
  const modelList = document.getElementById('modelList');

  // Filament -> form (single select)
  window.__selectedFilament = window.__selectedFilament || null;
  const selectedFilamentName = document.getElementById('selectedFilamentName');
  const selectedFilamentId = document.getElementById('selectedFilamentId');
  const selectedFilamentHint = document.getElementById('selectedFilamentHint');
  const goPickFilamentBtn = document.getElementById('goPickFilamentBtn');

  window.syncFilamentUI = function syncFilamentUI() {
    const selId = window.__selectedFilament && window.__selectedFilament.id;
    document.querySelectorAll('#filament .fc-item').forEach(card => {
      const fid = card.getAttribute('data-fid');
      const btn = card.querySelector('.fc-add');
      const isSel = selId && fid === selId;
      card.classList.toggle('selected', Boolean(isSel));
      if (btn) {
        btn.classList.toggle('is-selected', Boolean(isSel));
        btn.textContent = isSel ? 'Выбрано' : 'Добавить в заявку';
        btn.disabled = Boolean(isSel);
      }
    });
  };

  window.setSelectedFilament = function setSelectedFilament(f) {
    window.__selectedFilament = f ? { id: String(f.id), name: String(f.name) } : null;
    if (selectedFilamentName) selectedFilamentName.value = f ? f.name : '';
    if (selectedFilamentId) selectedFilamentId.value = f ? f.id : '';
    if (selectedFilamentHint) selectedFilamentHint.textContent = f ? `Выбран: ${f.name}` : 'Не выбран.';
    if (typeof window.syncFilamentUI === 'function') window.syncFilamentUI();
  };

  if (goPickFilamentBtn) {
    goPickFilamentBtn.addEventListener('click', () => {
      // на многостраничнике ведём на страницу филамента
      window.location.href = 'filament.html';
    });
  }

  function updateFormByType(type) {
    if (!attachmentsImagesBlock || !modelFileBlock || !materialsBlock || !strengthBlock) return;

    attachmentsImagesBlock.classList.remove('hidden');
    modelFileBlock.classList.add('hidden');

    materialsBlock.classList.add('hidden');
    strengthBlock.classList.add('hidden');

    if (modelFile) modelFile.required = false;

    if (type === 'modeling') {
      if (attachmentsHint) attachmentsHint.textContent = 'Прикрепите фото/скриншот чертежа/эскиза';
    }

    if (type === 'full') {
      if (attachmentsHint) attachmentsHint.textContent = 'Прикрепите фото сломанной детали/эскиз/чертеж/деталь, к которой должна крепиться';
      materialsBlock.classList.remove('hidden');
      strengthBlock.classList.remove('hidden');
    }

    if (type === 'print') {
      attachmentsImagesBlock.classList.add('hidden');
      modelFileBlock.classList.remove('hidden');
      materialsBlock.classList.remove('hidden');
      strengthBlock.classList.remove('hidden');
      if (modelFile) modelFile.required = true;
    }

    if (type === 'question') {
      if (attachmentsHint) attachmentsHint.textContent = 'Прикрепите фото/скрин (по желанию)';
    }
  }

  // initial state
  updateFormByType('modeling');

  document.querySelectorAll('input[name="requestType"]').forEach(r => {
    r.addEventListener('change', () => updateFormByType(r.value));
  });

  if (attachmentsImages) {
    attachmentsImages.addEventListener('change', () => setFilesList(imagesList, attachmentsImages.files));
  }
  if (modelFile) {
    modelFile.addEventListener('change', () => setFilesList(modelList, modelFile.files));
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const typeEl = document.querySelector('input[name="requestType"]:checked');
    const type = typeEl ? typeEl.value : 'modeling';

    if (!window.__selectedFilament || !window.__selectedFilament.id) {
      alert('Перед отправкой заявки нужно выбрать филамент.');
      // на многостраничнике ведём на страницу филамента
      window.location.href = 'filament.html';
      return;
    }

    if (type === 'print' && modelFile && (!modelFile.files || modelFile.files.length === 0)) {
      alert('Для "Печать" нужно прикрепить STL/STP/STEP файл.');
      return;
    }

    alert('✅ Запрос принят. Мы свяжемся в течение часа.');
    e.target.reset();
    if (typeof window.setSelectedFilament === 'function') window.setSelectedFilament(null);
    if (imagesList) imagesList.innerHTML = '';
    if (modelList) modelList.innerHTML = '';
    updateFormByType('modeling');
  });

  // expose for calculator transfer
  window.__updateFormByType = updateFormByType;
})();

// ===== Calculator =====
(function initCalculator() {
  const materialEl = document.getElementById('calcMaterial');
  const weightEl = document.getElementById('calcWeightG');
  const qtyEl = document.getElementById('calcQty');
  const urgentEl = document.getElementById('calcUrgent');
  const strengthEl = document.getElementById('calcStrength');

  const outFinal = document.getElementById('calcFinal');
  const outOld = document.getElementById('calcOld');
  const outSub = document.getElementById('calcSub');
  const toFormBtn = document.getElementById('calcToFormBtn');

  if (!materialEl || !weightEl || !qtyEl || !urgentEl || !strengthEl || !outFinal || !outOld || !outSub || !toFormBtn) return;

  const MIN_ORDER = 1200;
  const MIN_PART  = 400;
  const URGENT_ADD = 700;
  const MODEL_FEE  = 400;

  const fmtMoney = (n) => {
    const x = Math.round(Number(n) || 0);
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
  };

  function getPPG() {
    const opt = materialEl.options[materialEl.selectedIndex];
    return Number(opt.getAttribute('data-ppg')) || 0;
  }

  function getDiscountByQty(qty) {
    if (qty >= 100) return 0.15;
    if (qty >= 50) return 0.10;
    if (qty >= 10) return 0.05;
    return 0;
  }

  function materialName() {
    const opt = materialEl.options[materialEl.selectedIndex];
    return opt ? opt.textContent.trim() : '';
  }

  function recalc() {
    const ppg = getPPG();
    const weightG = Math.max(0, Number(weightEl.value) || 0);
    const qty = Math.max(1, Math.floor(Number(qtyEl.value) || 1));
    const urgent = Number(urgentEl.value) === 1;
    const discount = getDiscountByQty(qty);

    const plasticOne = weightG * ppg;
    const onePart = Math.max(MIN_PART, plasticOne);

    const printingSum = onePart * qty;
    const urgentFee = urgent ? URGENT_ADD : 0;

    const oldBase = printingSum + MODEL_FEE + urgentFee;
    const oldPrice = Math.max(MIN_ORDER, oldBase);

    const discountedPrinting = printingSum * (1 - discount);
    const finalBase = discountedPrinting + MODEL_FEE + urgentFee;
    const finalPrice = Math.max(MIN_ORDER, finalBase);

    outFinal.textContent = fmtMoney(finalPrice);

    if (discount > 0 && finalPrice < oldPrice) {
      outOld.textContent = fmtMoney(oldPrice);
      outOld.classList.remove('hidden');
    } else {
      outOld.classList.add('hidden');
    }

    const discountText = discount > 0 ? `скидка -${Math.round(discount * 100)}%` : 'без скидки';
    outSub.textContent =
      `${materialName()}, ${weightG} г, ${qty} шт, ${urgent ? 'срочно' : 'стандарт'}, ${discountText} (+400 ₽ за модель)`;
  }

  function pushToForm() {
    // на многостраничнике просто переходим на contacts.html
    const url = new URL('contacts.html', window.location.href);
    url.searchParams.set('fromCalc', '1');
    url.searchParams.set('material', materialEl.value);
    url.searchParams.set('weightG', String(Math.max(0, Number(weightEl.value) || 0)));
    url.searchParams.set('qty', String(Math.max(1, Math.floor(Number(qtyEl.value) || 1))));
    url.searchParams.set('urgent', String(Number(urgentEl.value) === 1 ? 1 : 0));
    url.searchParams.set('strength', String(strengthEl.checked ? 1 : 0));
    window.location.href = url.toString();
  }

  [materialEl, weightEl, qtyEl, urgentEl, strengthEl].forEach(el => {
    el.addEventListener('change', recalc);
    el.addEventListener('input', recalc);
  });

  toFormBtn.addEventListener('click', pushToForm);

  recalc();
})();

// Apply calculator params on contacts page (from query string)
(function applyCalcToForm() {
  const form = document.getElementById('requestForm');
  if (!form) return;

  const params = new URLSearchParams(window.location.search);
  if (!params.get('fromCalc')) return;

  const material = params.get('material');
  const weightG = params.get('weightG');
  const qty = params.get('qty');
  const urgent = params.get('urgent') === '1';
  const strength = params.get('strength') === '1';

  // switch to print type
  const printRadio = document.querySelector('input[name="requestType"][value="print"]');
  if (printRadio) {
    printRadio.checked = true;
    if (typeof window.__updateFormByType === 'function') window.__updateFormByType('print');
  }

  const formMaterial = document.getElementById('materialSelect');
  if (formMaterial && material) formMaterial.value = material;

  const strengthNeed = document.getElementById('strengthNeed');
  if (strengthNeed) strengthNeed.checked = strength;

  const desc = document.getElementById('taskDesc');
  if (desc) {
    const block =
      `Параметры печати (из калькулятора):\n` +
      `- Материал: ${material || ''}\n` +
      `- Вес (оценка): ${weightG || ''} г (1 шт)\n` +
      `- Количество: ${qty || ''} шт\n` +
      `- Срочность: ${urgent ? 'срочно' : 'стандарт'}\n` +
      `- Повышенная прочность: ${strength ? 'да' : 'нет'}\n`;

    const cur = desc.value.trim();
    const cleaned = cur.replace(/Параметры печати \(из калькулятора\):[\s\S]*$/m, '').trim();
    desc.value = (cleaned + (cleaned ? '\n\n' : '') + block).trim();
  }
})();

// ===== Filament carousel =====
(async function initFilamentCarousel() {
  const viewport = document.getElementById('fcViewport');
  const dotsWrap = document.getElementById('fcDots');
  const prevBtn = document.getElementById('fcPrev');
  const nextBtn = document.getElementById('fcNext');
  if (!viewport || !dotsWrap || !prevBtn || !nextBtn) return;

  function toBool(v) {
    if (typeof v === 'boolean') return v;
    if (typeof v === 'number') return v > 0;
    if (typeof v === 'string') {
      const s = v.trim().toLowerCase();
      if (s.includes('налич')) return true;
      if (s.includes('заказ') || s.includes('нет') || s.includes('out')) return false;
      if (['1','true','yes','y','on','available','instock','in'].includes(s)) return true;
      if (['0','false','no','n','off','preorder','unavailable'].includes(s)) return false;
    }
    return Boolean(v);
  }

  function normalizeFilament(it, idx) {
    const rawStock = (
      it?.inStock ?? it?.instock ?? it?.in_stock ?? it?.isInStock ?? it?.is_in_stock ??
      it?.available ?? it?.isAvailable ?? it?.availability ?? it?.status ?? it?.stock_status ??
      it?.stock ?? it?.qty ?? it?.quantity
    );
    return {
      id: String(it?.id ?? it?.slug ?? it?.code ?? ('f_' + idx)),
      name: String(it?.name ?? it?.title ?? ('Филамент ' + (idx + 1))),
      image: String(it?.image ?? it?.img ?? it?.photo ?? ''),
      inStock: toBool(rawStock)
    };
  }

  function parseItems(any) {
    try {
      const v = (typeof any === 'string') ? JSON.parse(any) : any;
      if (Array.isArray(v)) return v;
      if (Array.isArray(v?.items)) return v.items;
      if (Array.isArray(v?.filaments)) return v.filaments;
      return null;
    } catch (e) {
      return null;
    }
  }

  function getAdminRaw() {
    return (
      window.FILAMENTS_FROM_ADMIN ??
      window.FILAMENTSFROMADMIN ??
      window.FILAMENTS ??
      window.filaments
    );
  }

  async function loadFilamentsFromJson() {
    const urls = [
      'content/filament.json','./content/filament.json',
      'content/filaments.json','./content/filaments.json',
      'filament.json','./filament.json'
    ];

    for (const base of urls) {
      try {
        const url = new URL(base, window.location.href).toString() + '?ts=' + Date.now();
        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) continue;
        const data = await res.json();
        const items = parseItems(data);
        if (items && items.length) return items.map(normalizeFilament);
      } catch (e) {}
    }
    return null;
  }

  const adminItems = parseItems(getAdminRaw());
  let filaments = (adminItems && adminItems.length) ? adminItems.map(normalizeFilament) : null;
  if (!filaments || filaments.length === 0) filaments = await loadFilamentsFromJson();

  if (!Array.isArray(filaments) || filaments.length === 0) {
    viewport.innerHTML = '<div class="hint" style="text-align:left;">Филамент не загружен. Проверьте админ‑панель или JSON.</div>';
    dotsWrap.innerHTML = '';
    prevBtn.disabled = true;
    nextBtn.disabled = true;
    return;
  }

  function getItemStepPx() {
    const first = viewport.querySelector('.fc-item');
    if (!first) return viewport.clientWidth;
    const style = getComputedStyle(viewport);
    const gap = parseFloat(style.columnGap || style.gap || '0') || 0;
    return first.getBoundingClientRect().width + gap;
  }

  function scrollToIndex(index) {
    const idx = Math.max(0, Math.min(filaments.length - 1, index));
    const step = getItemStepPx();
    viewport.scrollTo({ left: step * idx, behavior: 'smooth' });
  }

  function currentIndex() {
    const step = getItemStepPx();
    if (!step) return 0;
    return Math.max(0, Math.min(filaments.length - 1, Math.round(viewport.scrollLeft / step)));
  }

  function updateActiveDot() {
    const idx = currentIndex();
    [...dotsWrap.querySelectorAll('.fc-dot')].forEach((d, i) => d.classList.toggle('active', i === idx));
  }

  function render() {
    viewport.innerHTML = '';
    dotsWrap.innerHTML = '';

    filaments.forEach((f, idx) => {
      const item = document.createElement('div');
      item.className = 'fc-item';
      item.dataset.index = String(idx);
      item.setAttribute('data-fid', String(f.id));

      const img = document.createElement('img');
      img.className = 'fc-img';
      img.alt = f.name;
      img.src = f.image || 'images/filament/placeholder.jpg';
      img.loading = 'lazy';
      img.onerror = () => { img.style.opacity = '0.15'; };

      const name = document.createElement('div');
      name.className = 'fc-name';
      name.textContent = f.name;

      const status = document.createElement('div');
      status.className = 'fc-status ' + (f.inStock ? 'in' : 'preorder');
      status.textContent = f.inStock ? 'В наличии' : 'Под заказ';

      const sub = document.createElement('div');
      sub.className = 'fc-sub';
      sub.textContent = f.inStock ? 'Есть на складе.' : 'Доставка в течение 1–3 дней.';

      const addBtn = document.createElement('button');
      addBtn.type = 'button';
      addBtn.className = 'fc-add';
      addBtn.textContent = 'Добавить в заявку';
      addBtn.addEventListener('click', () => {
        if (typeof window.setSelectedFilament === 'function') window.setSelectedFilament(f);
        window.location.href = 'contacts.html';
      });

      item.appendChild(img);
      item.appendChild(name);
      item.appendChild(status);
      item.appendChild(sub);
      item.appendChild(addBtn);

      viewport.appendChild(item);

      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'fc-dot' + (idx === 0 ? ' active' : '');
      dot.setAttribute('aria-label', 'Слайд ' + (idx + 1));
      dot.addEventListener('click', () => scrollToIndex(idx));
      dotsWrap.appendChild(dot);
    });

    if (typeof window.syncFilamentUI === 'function') window.syncFilamentUI();
    updateActiveDot();
  }

  prevBtn.addEventListener('click', () => scrollToIndex(currentIndex() - 1));
  nextBtn.addEventListener('click', () => scrollToIndex(currentIndex() + 1));
  viewport.addEventListener('scroll', () => window.requestAnimationFrame(updateActiveDot));

  render();
})();
