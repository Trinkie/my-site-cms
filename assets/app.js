(function () {
  // ===== helpers =====
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
      if (['0','false','no','n','off','preorder','unavailable','out'].includes(s)) return false;
      if (['1','true','yes','y','on','available','instock','in'].includes(s)) return true;
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

  async function loadFilaments() {
    // 1) admin injected (если когда-то вернёшь)
    const adminItems = parseItems(window.FILAMENTSFROMADMIN ?? window.FILAMENTS ?? window.filaments);
    if (adminItems && adminItems.length) return adminItems.map(normalizeFilament);

    // 2) file fallback (твой случай)
    const urls = [
      'filaments.json',
      'content/filaments.json',
      'content/filament.json',
      'filament.json'
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

  // ===== modal helpers (NO alerts) =====
  function openModal(modalEl) {
    if (!modalEl) return;
    modalEl.classList.remove('hidden');

    // close on background click
    const onBg = (e) => {
      if (e.target === modalEl) modalEl.classList.add('hidden');
    };
    modalEl.addEventListener('click', onBg, { once: true });

    // close on Esc
    const onEsc = (e) => {
      if (e.key === 'Escape') {
        modalEl.classList.add('hidden');
        document.removeEventListener('keydown', onEsc);
      }
    };
    document.addEventListener('keydown', onEsc);
  }

  function closeModal(modalEl) {
    if (!modalEl) return;
    modalEl.classList.add('hidden');
  }

  function showOrderError(msg) {
    const modal = document.getElementById('orderErrorModal');
    const text = document.getElementById('orderErrorText');
    const closeBtn = document.getElementById('closeOrderError');

    if (text) text.textContent = msg || 'Не удалось выполнить действие.';
    if (closeBtn) closeBtn.onclick = () => closeModal(modal);
    openModal(modal);
  }

  function showOrderSuccess() {
    const modal = document.getElementById('orderSuccessModal');
    const closeBtn = document.getElementById('closeOrderSuccess');
    if (closeBtn) closeBtn.onclick = () => closeModal(modal);
    openModal(modal);
  }

  // ===== filament carousel =====
  async function initFilamentCarouselOnce() {
    const viewport = document.getElementById('fcViewport');
    const dotsWrap = document.getElementById('fcDots');
    const prevBtn = document.getElementById('fcPrev');
    const nextBtn = document.getElementById('fcNext');

    if (!viewport || !dotsWrap || !prevBtn || !nextBtn) return;
    if (viewport.dataset.inited === '1') return;
    viewport.dataset.inited = '1';

    const selectedFilamentName = document.getElementById('selectedFilamentName');
    const selectedFilamentId = document.getElementById('selectedFilamentId');
    const selectedFilamentHint = document.getElementById('selectedFilamentHint');

    window.selectedFilament = window.selectedFilament ?? null;

    function syncUI() {
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

    function setSelected(f) {
      window.selectedFilament = f ? { id: String(f.id), name: String(f.name) } : null;
      if (selectedFilamentName) selectedFilamentName.value = f ? f.name : '';
      if (selectedFilamentId) selectedFilamentId.value = f ? f.id : '';
      if (selectedFilamentHint) selectedFilamentHint.textContent = f ? `Выбран: ${f.name}` : 'Филамент не выбран.';
      syncUI();
    }

    const filaments = await loadFilaments();

    if (!Array.isArray(filaments) || filaments.length === 0) {
      viewport.innerHTML = '<div class="hint">Не найден filaments.json (или он пустой).</div>';
      dotsWrap.innerHTML = '';
      prevBtn.disabled = true;
      nextBtn.disabled = true;
      return;
    }

    function getStep() {
      const first = viewport.querySelector('.fc-item');
      if (!first) return viewport.clientWidth;
      const style = getComputedStyle(viewport);
      const gap = parseFloat(style.columnGap || style.gap || '0') || 0;
      return first.getBoundingClientRect().width + gap;
    }

    function scrollToIndex(i) {
      const idx = Math.max(0, Math.min(filaments.length - 1, i));
      viewport.scrollTo({ left: getStep() * idx, behavior: 'smooth' });
    }

    function currentIndex() {
      const step = getStep();
      if (!step) return 0;
      return Math.max(0, Math.min(filaments.length - 1, Math.round(viewport.scrollLeft / step)));
    }

    function updateDot() {
      const idx = currentIndex();
      dotsWrap.querySelectorAll('.fc-dot').forEach((d, i) => d.classList.toggle('active', i === idx));
    }

    // render
    viewport.innerHTML = '';
    dotsWrap.innerHTML = '';

    filaments.forEach((f, idx) => {
      const item = document.createElement('div');
      item.className = 'fc-item';
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

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'fc-add';
      btn.textContent = 'Выбрать';
      btn.addEventListener('click', () => setSelected(f));

      item.appendChild(img);
      item.appendChild(name);
      item.appendChild(status);
      item.appendChild(btn);
      viewport.appendChild(item);

      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'fc-dot' + (idx === 0 ? ' active' : '');
      dot.addEventListener('click', () => scrollToIndex(idx));
      dotsWrap.appendChild(dot);
    });

    prevBtn.addEventListener('click', () => scrollToIndex(currentIndex() - 1));
    nextBtn.addEventListener('click', () => scrollToIndex(currentIndex() + 1));
    viewport.addEventListener('scroll', () => window.requestAnimationFrame(updateDot));

    syncUI();
    updateDot();
    setTimeout(updateDot, 150);
  }

  // ===== order wizard =====
  (function initWizard() {
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
    let seq = [];
    let idx = 0;

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

    function getServiceType() {
      const el = form.querySelector('input[name="serviceType"]:checked');
      return el ? el.value : null;
    }

    function stageSequence() {
      const t = getServiceType();
      const s = [1, 2];
      if (t === 'print' || t === 'full') s.push(3);
      s.push(4);
      return s;
    }

    function showStage() {
      seq = stageSequence();
      if (idx >= seq.length) idx = seq.length - 1;

      const cur = seq[idx];
      stages.forEach(s => s.classList.toggle('hidden', Number(s.dataset.stage) !== cur));

      if (stageText) stageText.textContent = `Шаг ${idx + 1} из ${seq.length}`;
      if (btnPrev) btnPrev.disabled = idx === 0;
      if (btnNext) btnNext.style.display = (idx === seq.length - 1) ? 'none' : '';

      // init carousel only when step 3 opened
      if (cur === 3 && !filamentInited) {
        filamentInited = true;
        initFilamentCarouselOnce();
      }

      const submitWrap = document.getElementById('submitWrap');
      if (submitWrap) submitWrap.classList.toggle('hidden', cur !== 4);
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

      if (t === 'question') {
        // вопрос: файлы не обязательны
        if (filesLabel) filesLabel.textContent = 'Файлы (если нужно)';
        if (filesHint) filesHint.textContent = 'Можно прикрепить файлы, но это не обязательно.';
        if (filesInput) {
          filesInput.accept = '';
          filesInput.multiple = true;
          filesInput.required = false;
        }
      }

      showStage();
    }

    form.querySelectorAll('input[name="serviceType"]').forEach(r => {
      r.addEventListener('change', syncFilesByType);
    });

    if (filesInput) filesInput.addEventListener('change', () => setFilesList(filesList, filesInput.files));

    function validateCurrentStage() {
      const curStageNum = seq[idx];
      const curStageEl = form.querySelector(`.wiz-stage[data-stage="${curStageNum}"]`);
      if (!curStageEl) return true;

      const els = Array.from(curStageEl.querySelectorAll('input, textarea, select'));
      for (const el of els) {
        if (el.required && !el.checkValidity()) {
          el.reportValidity();
          return false;
        }
      }

      if (curStageNum === 3) {
        const id = document.getElementById('selectedFilamentId')?.value;
        if (!id) {
          showOrderError('Выберите филамент, чтобы продолжить.');
          return false;
        }
      }

      return true;
    }

    if (btnNext) btnNext.addEventListener('click', () => {
      if (!validateCurrentStage()) return;
      idx = Math.min(idx + 1, stageSequence().length - 1);
      showStage();
    });

    if (btnPrev) btnPrev.addEventListener('click', () => {
      idx = Math.max(idx - 1, 0);
      showStage();
    });

    // ===== submit (NO alerts) =====
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // финальная проверка
      if (!validateCurrentStage()) return;

      const serviceType = form.querySelector('input[name="serviceType"]:checked')?.value || '';

      const data = new FormData();
      data.append('serviceType', serviceType);
      data.append('clientContact', document.getElementById('clientContact')?.value || '');
      data.append('taskDesc', document.getElementById('taskDesc')?.value || '');

      data.append('filamentId', document.getElementById('selectedFilamentId')?.value || '');
      data.append('filamentName', document.getElementById('selectedFilamentName')?.value || '');

      const fi = document.getElementById('filesInput');
      if (fi && fi.files && fi.files.length) {
        [...fi.files].forEach((f, i) => data.append(`file_${i}`, f));
      }

      // блокируем кнопку отправки, если есть
      const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
      const prevDisabled = submitBtn ? submitBtn.disabled : false;
      if (submitBtn) submitBtn.disabled = true;

      try {
        const r = await fetch('api/order.php', { method: 'POST', body: data });
        const j = await r.json().catch(() => ({}));

        if (!r.ok || !j.ok) {
          showOrderError('Не удалось отправить заказ. Попробуйте ещё раз.');
          if (submitBtn) submitBtn.disabled = prevDisabled;
          return;
        }

        // успех
        form.reset();
        if (filesList) filesList.innerHTML = '';
        window.selectedFilament = null;

        // сброс выбранного филамента в UI, если есть элементы
        const selectedFilamentName = document.getElementById('selectedFilamentName');
        const selectedFilamentId = document.getElementById('selectedFilamentId');
        const selectedFilamentHint = document.getElementById('selectedFilamentHint');
        if (selectedFilamentName) selectedFilamentName.value = '';
        if (selectedFilamentId) selectedFilamentId.value = '';
        if (selectedFilamentHint) selectedFilamentHint.textContent = 'Филамент не выбран.';

        idx = 0;
        syncFilesByType();
        showStage();

        showOrderSuccess();

      } catch (err) {
        showOrderError('Ошибка сети. Попробуйте ещё раз.');
      } finally {
        if (submitBtn) submitBtn.disabled = prevDisabled;
      }
    });

    // init
    syncFilesByType();
    showStage();
  })();
})();
