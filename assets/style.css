(function () {
  // ========== NAV COLLAPSE (mobile arrow) ==========
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

  // ========== NETLIFY IDENTITY (persist login + show logout button) ==========
  (function identity() {
    const logoutBtn = document.getElementById('logoutBtn');

    function syncAuthUI() {
      const has = window.netlifyIdentity && window.netlifyIdentity.currentUser();
      if (logoutBtn) logoutBtn.style.display = has ? '' : 'none';
    }

    if (window.netlifyIdentity) {
      window.netlifyIdentity.on('init', syncAuthUI);
      window.netlifyIdentity.on('login', syncAuthUI);
      window.netlifyIdentity.on('logout', syncAuthUI);
      window.netlifyIdentity.init();
    }

    if (logoutBtn) {
      logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (window.netlifyIdentity) window.netlifyIdentity.logout();
      });
    }

    window.addEventListener('load', syncAuthUI);
  })();

  // ========== ORDER WIZARD ==========
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

    const filamentStage = document.getElementById('filamentStage');

    function setFilesList(target, files) {
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

      // show/hide filament stage container (just in case)
      if (filamentStage) filamentStage.classList.toggle('hidden', cur !== 3);

      // submit shown only at stage 4
      const submitWrap = document.getElementById('submitWrap');
      if (submitWrap) submitWrap.classList.toggle('hidden', cur !== 4);
    }

    function validateCurrentStage() {
      const curStageNum = seq[idx];
      const curStageEl = form.querySelector(`.wiz-stage[data-stage="${curStageNum}"]`);
      if (!curStageEl) return true;

      // validate required fields only inside current stage
      const els = Array.from(curStageEl.querySelectorAll('input, textarea, select'));
      for (const el of els) {
        if (el.required && !el.checkValidity()) {
          el.reportValidity();
          return false;
        }
      }

      // validate filament on stage 3
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

      // reset selection when type changes
      if (filesInput) filesInput.value = '';
      if (filesList) setFilesList(filesList, null);

      // set requirements
      if (t === 'full' || t === 'modeling') {
        filesLabel.textContent = 'Эскизы / чертежи / фото детали';
        filesHint.textContent = 'Прикрепите эскиз/чертёж/фото. Можно несколько файлов.';
        filesInput.accept = 'image/*,.pdf';
        filesInput.multiple = true;
        filesInput.required = true;
      }

      if (t === 'print') {
        filesLabel.textContent = 'Файл модели (STL / STP / STEP)';
        filesHint.textContent = 'Прикрепите файл STL/STP/STEP.';
        filesInput.accept = '.stl,.stp,.step';
        filesInput.multiple = false;
        filesInput.required = true;
      }

      // stage sequence may change (print/full adds filament stage)
      showStage();
    }

    // event: service type change
    form.querySelectorAll('input[name="serviceType"]').forEach((r) => {
      r.addEventListener('change', syncFilesByType);
    });

    // files list
    if (filesInput) {
      filesInput.addEventListener('change', () => {
        setFilesList(filesList, filesInput.files);
      });
    }

    // next/prev buttons
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

    // ========== FILAMENT CAROUSEL (same logic as old) ==========
    window.selectedFilament = null;

    const selectedFilamentName = document.getElementById('selectedFilamentName');
    const selectedFilamentId = document.getElementById('selectedFilamentId');
    const selectedFilamentHint = document.getElementById('selectedFilamentHint');

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

    async function loadFilamentsFromJson() {
      const urls = [
        'content/filament.json','content/filaments.json',
        'filament.json','filaments.json'
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

    async function initFilamentCarousel() {
      const viewport = document.getElementById('fcViewport');
      const dotsWrap = document.getElementById('fcDots');
      const prevBtn = document.getElementById('fcPrev');
      const nextBtn = document.getElementById('fcNext');

      if (!viewport || !dotsWrap || !prevBtn || !nextBtn) return;

      const adminRaw = window.FILAMENTSFROMADMIN ?? window.FILAMENTS ?? window.filaments;
      const adminItems = parseItems(adminRaw);

      let filaments = (adminItems && adminItems.length) ? adminItems.map(normalizeFilament) : null;
      if (!filaments || filaments.length === 0) filaments = await loadFilamentsFromJson();

      if (!Array.isArray(filaments) || filaments.length === 0) {
        viewport.innerHTML = '<div class="hint">Нет списка филаментов. Добавьте content/filament.json</div>';
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

        syncFilamentUI();
        updateActiveDot();

        prevBtn.addEventListener('click', () => scrollToIndex(currentIndex() - 1));
        nextBtn.addEventListener('click', () => scrollToIndex(currentIndex() + 1));
        viewport.addEventListener('scroll', () => window.requestAnimationFrame(updateActiveDot));
      }

      render();
    }

    initFilamentCarousel();

    // submit (demo)
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      // validate all required fields by running through stages quickly
      const t = getServiceType();
      if (!t) { alert('Выберите услугу.'); return; }

      // filament check if required
      if ((t === 'print' || t === 'full') && !selectedFilamentId.value) {
        alert('Выберите филамент.');
        idx = Math.max(0, stageSequence().indexOf(3));
        showStage();
        return;
      }

      alert('Заказ сформирован (здесь подключим отправку на сервер/Telegram).');
      form.reset();
      setSelectedFilament(null);
      if (filesList) filesList.innerHTML = '';
      idx = 0;
      showStage();
    });

    // init defaults
    showStage();
  })();
})();
