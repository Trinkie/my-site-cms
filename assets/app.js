(function () {
  // =========================
  // Helpers
  // =========================
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

  function toBool(v) {
    if (typeof v === 'boolean') return v;
    if (typeof v === 'number') return v !== 0;
    if (typeof v === 'string') {
      const s = v.trim().toLowerCase();
      if (s.includes('out')) return false;
      if (['1','true','yes','y','on','available','instock','in'].includes(s)) return true;
      if (['0','false','no','n','off','preorder','unavailable'].includes(s)) return false;
    }
    return Boolean(v);
  }

  function normalizeFilament(it, idx) {
    const rawStock =
      it?.inStock ?? it?.instock ?? it?.isInStock ?? it?.available ?? it?.status ?? it?.stock ?? it?.qty ?? it?.quantity;

    return {
      id: String(it?.id ?? it?.slug ?? it?.code ?? `f${idx}`),
      name: String(it?.name ?? it?.title ?? `Filament ${idx + 1}`),
      image: String(it?.image ?? it?.img ?? it?.photo ?? ''),
      inStock: toBool(rawStock)
    };
  }

  function getAdminFilamentsRaw() {
    // “как делали”: админка должна положить список в одну из глобальных переменных
    return (window.FILAMENTSFROMADMIN ?? window.FILAMENTS ?? window.filaments ?? null);
  }

  async function loadFilamentsFromJsonFallback() {
    const urls = [
      'content/filament.json',
      'content/filaments.json',
      'filament.json',
      'filaments.json'
    ];

    for (const base of urls) {
      try {
        const url = new URL(base, window.location.href);
        url.searchParams.set('ts', String(Date.now()));
        const res = await fetch(url.toString(), { cache: 'no-store' });
        if (!res.ok) continue;
        const data = await res.json();
        const items = parseItems(data);
        if (items && items.length) return items.map(normalizeFilament);
      } catch (e) {}
    }
    return null;
  }

  // =========================
  // Filament carousel (shared)
  // =========================
  async function initFilamentCarouselOnce(opts) {
    const viewport = document.getElementById('fcViewport');
    const dotsWrap = document.getElementById('fcDots');
    const prevBtn = document.getElementById('fcPrev');
    const nextBtn = document.getElementById('fcNext');

    if (!viewport || !dotsWrap || !prevBtn || !nextBtn) return;

    // avoid double init
    if (viewport.dataset.inited === '1') return;
    viewport.dataset.inited = '1';

    // selection outputs (optional)
    const selectedFilamentName = document.getElementById('selectedFilamentName');
    const selectedFilamentId = document.getElementById('selectedFilamentId');
    const selectedFilamentHint = document.getElementById('selectedFilamentHint');

    window.selectedFilament = window.selectedFilament ?? null;

    function syncFilamentUI() {
      const selId = window.selectedFilament?.id;
      document.querySelectorAll('.fc-item').forEach(card => {
        const fid = card.getAttribute('data-fid');
        const btn = card.querySelector('.fc-add');
        const isSel = selId && fid === selId;

        card.classList.toggle('selected', Boolean(isSel));
        if (btn) {
          btn.classList.toggle('is-selected', Boolean(isSel));
          btn.textContent = isSel ? 'Выбрано' : 'Выбрать';
          btn.disabled = Boolean(isSel);
        }
      });
    }

    function setSelectedFilament(f) {
      window.selectedFilament = f ? { id: String(f.id), name: String(f.name) } : null;

      if (selectedFilamentName) selectedFilamentName.value = f ? f.name : '';
      if (selectedFilamentId) selectedFilamentId.value = f ? f.id : '';
      if (selectedFilamentHint) selectedFilamentHint.textContent = f ? `Выбран: ${f.name}` : 'Филамент не выбран.';

      syncFilamentUI();
    }

    // 1) try admin
    const adminItems = parseItems(getAdminFilamentsRaw());
    let filaments = (adminItems && adminItems.length) ? adminItems.map(normalizeFilament) : null;

    // 2) fallback to json
    if (!filaments || filaments.length === 0) {
      filaments = await loadFilamentsFromJsonFallback();
    }

    if (!Array.isArray(filaments) || filaments.length === 0) {
      viewport.innerHTML = '<div class="hint">Нет списка филаментов (не пришло из админки и нет JSON-файла).</div>';
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
      dotsWrap.querySelectorAll('.fc-dot').forEach((d, i) => d.classList.toggle('active', i === idx));
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
        sub.textContent = f.inStock
          ? 'Обычно печатаем быстрее.'
          : 'Сроки могут быть больше (уточним).';

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'fc-add';
        addBtn.textContent = 'Выбрать';
        addBtn.addEventListener('click', () => setSelectedFilament(f));

        item.appendChild(img);
        item.appendChild(name);
        item.appendChild(status);
        item.appendChild(sub);
        item.appendChild(addBtn);
        viewport.appendChild(item);

        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'fc-dot' + (idx === 0 ? ' active' : '');
        dot.setAttribute('aria-label', String(idx + 1));
        dot.addEventListener('click', () => scrollToIndex(idx));
        dotsWrap.appendChild(dot);
      });

      // buttons + scroll
      prevBtn.addEventListener('click', () => scrollToIndex(currentIndex() - 1));
      nextBtn.addEventListener('click', () => scrollToIndex(currentIndex() + 1));
      viewport.addEventListener('scroll', () => window.requestAnimationFrame(updateActiveDot));

      syncFilamentUI();
      updateActiveDot();
    }

    render();

    // If carousel created while hidden, re-calc after a tick
    setTimeout(() => {
      updateActiveDot();
    }, 150);

    // expose setter for wizard validation if needed
    window.__setSelectedFilament = setSelectedFilament;
  }

  // =========================
  // NAV collapse (mobile)
  // =========================
  (function navCollapse() {
    const nav = document.querySelector('.nav-glass');
    const btn = document.getElementById('navToggle');
    if (!nav || !btn) return;

    const KEY = 'nav_collapsed';

    function apply(collapsed) {
      nav.classList.toggle('is-collapsed', collapsed);
      btn.textContent = collapsed ? '›' : '‹';
      btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }

    apply(localStorage.getItem(KEY) === '1');

    btn.addEventListener('click', () => {
      const next = !nav.classList.contains('is-collapsed');
      localStorage.setItem(KEY, next ? '1' : '0');
      apply(next);
    });
  })();

  // =========================
  // Standalone filament page init
  // =========================
  (function standaloneFilamentPage() {
    // Если на странице есть карусель — инициализируем сразу
    if (document.getElementById('fcViewport') && !document.getElementById('orderWizard')) {
      initFilamentCarouselOnce();
    }
  })();

  // =========================
  // ORDER WIZARD (contacts.php)
  // =========================
  (function orderWizard() {
    const form = document.getElementById('orderWizard');
    if (!form) return;

    const stages = Array.from(form.querySelectorAll('.wiz-stage'));
    const btnPrev = document.getElementById('prevStage');
    const btnNext = document.getElementById('nextStage');
    const stageText = document.getElementById('stageText');

    const filesInput = document.getElementById('filesInput');
    const filesLabel = document.getElementById('filesLabel');
    const filesHint = document.getElementById('filesHint');
    const filesList = document.getElementById('filesList');

    let filamentInited = false;

    function setFilesList(target, files) {
      if (!target) return;
      target.innerHTML = '';
      if (!files || files.length === 0) return;
      [...files].forEach((f) => {
        const div = document.createElement('div');
        div.className = 'item';
        div.textContent = `${f.name} (${Math.round(f.size / 1024)} KB)`;
        target.appendChild(div);
      });
    }

    function getServiceType() {
      const el = form.querySelector('input[name="serviceType"]:checked');
      return el ? el.value : null;
    }

    function stageSequence() {
      const t = getServiceType();
      const seq = [1, 2];
      if (t === 'print' || t === 'full') seq.push(3);
      seq.push(4);
      return seq;
    }

    let seq = stageSequence();
    let idx = 0;

    function showStage() {
      seq = stageSequence();
      if (idx >= seq.length) idx = seq.length - 1;

      const cur = seq[idx];
      const total = seq.length;

      stages.forEach((s) => {
        const n = Number(s.dataset.stage);
        s.classList.toggle('hidden', n !== cur);
      });

      if (stageText) stageText.textContent = `Шаг ${idx + 1} из ${total}`;

      if (btnPrev) btnPrev.disabled = idx === 0;
      if (btnNext) btnNext.style.display = (idx === total - 1) ? 'none' : '';

      // Lazy init filament carousel ONLY when step 3 is opened
      if (cur === 3 && !filamentInited) {
        filamentInited = true;
        initFilamentCarouselOnce();
      }

      const submitWrap = document.getElementById('submitWrap');
      if (submitWrap) submitWrap.classList.toggle('hidden', cur !== 4);
    }

    function validateCurrentStage() {
      const curStageNum = seq[idx];
      const curStageEl = form.querySelector(`.wiz-stage[data-stage="${curStageNum}"]`);
      if (!curStageEl) return true;

      // required only in current stage
      const els = Array.from(curStageEl.querySelectorAll('input, textarea, select'));
      for (const el of els) {
        if (el.required && !el.checkValidity()) {
          el.reportValidity();
          return false;
        }
      }

      // If filament step is present: require selection
      if (curStageNum === 3) {
        const id = document.getElementById('selectedFilamentId')?.value;
        if (!id) {
          alert('Выберите филамент.');
          return false;
        }
      }

      return true;
    }

    function syncFilesByType() {
      const t = getServiceType();
      if (!t) return;

      if (filesInput) filesInput.value = '';
      if (filesList) setFilesList(filesList, null);

      if (t === 'full' || t === 'modeling') {
        if (filesLabel) filesLabel.textContent = 'Эскизы / чертежи / фото детали';
        if (filesHint) filesHint.textContent = 'Прикрепите эскиз/чертёж/фото. Можно несколько файлов.';
        if (filesInput) {
          filesInput.accept = 'image/*,.pdf';
          filesInput.multiple = true;
          filesInput.required = true;
        }
      }

      if (t === 'print') {
        if (filesLabel) filesLabel.textContent = 'Файл модели (STL / STP / STEP)';
        if (filesHint) filesHint.textContent = 'Прикрепите файл STL/STP/STEP.';
        if (filesInput) {
          filesInput.accept = '.stl,.stp,.step';
          filesInput.multiple = false;
          filesInput.required = true;
        }
      }

      showStage();
    }

    form.querySelectorAll('input[name="serviceType"]').forEach((r) => {
      r.addEventListener('change', syncFilesByType);
    });

    if (filesInput) {
      filesInput.addEventListener('change', () => {
        setFilesList(filesList, filesInput.files);
      });
    }

    if (btnNext) {
      btnNext.addEventListener('click', () => {
        if (!validateCurrentStage()) return;
        idx = Math.min(idx + 1, stageSequence().length - 1);
        showStage();
      });
    }

    if (btnPrev) {
      btnPrev.addEventListener('click', () => {
        idx = Math.max(idx - 1, 0);
        showStage();
      });
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const t = getServiceType();
      if (!t) { alert('Выберите услугу.'); return; }

      if ((t === 'print' || t === 'full') && !document.getElementById('selectedFilamentId')?.value) {
        alert('Выберите филамент.');
        // jump to filament stage
        const pos = stageSequence().indexOf(3);
        if (pos >= 0) idx = pos;
        showStage();
        return;
      }

      alert('Заказ сформирован (дальше подключим отправку).');
      form.reset();

      // reset selection
      if (typeof window.__setSelectedFilament === 'function') window.__setSelectedFilament(null);
      if (filesList) filesList.innerHTML = '';

      idx = 0;
      filamentInited = false;
      // allow init again after reset
      const viewport = document.getElementById('fcViewport');
      if (viewport) delete viewport.dataset.inited;

      showStage();
    });

    showStage();
  })();
})();
