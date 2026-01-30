(() => {
  const form = document.getElementById('orderWizard');
  if (!form) return;

  const stages = Array.from(form.querySelectorAll('.wiz-stage'));
  const stageText = document.getElementById('stageText');

  const btnNext = document.getElementById('nextStage');
  const btnPrevTop = document.getElementById('prevStageTop');
  const btnPrevBottom = document.getElementById('prevStage');

  const successModal = document.getElementById('orderSuccessModal');
  const closeOrderSuccess = document.getElementById('closeOrderSuccess');

  const errorModal = document.getElementById('orderErrorModal');
  const closeOrderError = document.getElementById('closeOrderError');
  const orderErrorText = document.getElementById('orderErrorText');

  const getServiceType = () =>
    form.querySelector('input[name="serviceType"]:checked')?.value || 'modeling';

  let idx = 0;

  const seq = () => {
    const t = getServiceType();
    const s = [1, 2];
    if (t === 'print' || t === 'full') s.push(3);
    s.push(4);
    return s;
  };

  const showStage = () => {
    const s = seq();
    if (idx < 0) idx = 0;
    if (idx > s.length - 1) idx = s.length - 1;

    const curStage = s[idx];

    stages.forEach(el => {
      el.classList.toggle('hidden', Number(el.dataset.stage) !== curStage);
    });

    if (stageText) stageText.textContent = `Шаг ${idx + 1} из ${s.length}`;

    const isFirst = idx === 0;
    const isLast = idx === s.length - 1;

    if (btnPrevTop) btnPrevTop.disabled = isFirst;
    if (btnPrevBottom) btnPrevBottom.disabled = isFirst;

    if (btnNext) btnNext.style.display = isLast ? 'none' : '';
  };

  const openModal = (modalEl) => {
    if (!modalEl) return;
    modalEl.classList.remove('hidden');

    const onBg = (e) => { if (e.target === modalEl) modalEl.classList.add('hidden'); };
    modalEl.addEventListener('click', onBg, { once: true });
  };

  const closeModal = (modalEl) => {
    if (!modalEl) return;
    modalEl.classList.add('hidden');
  };

  closeOrderSuccess?.addEventListener('click', () => closeModal(successModal));
  closeOrderError?.addEventListener('click', () => closeModal(errorModal));

  const validateCurrent = () => {
    const s = seq();
    const cur = s[idx];
    const el = form.querySelector(`.wiz-stage[data-stage="${cur}"]`);
    if (!el) return true;

    const invalid = el.querySelector('[required]:invalid');
    if (invalid) {
      invalid.reportValidity();
      return false;
    }

    if (cur === 3) {
      const fid = document.getElementById('selectedFilamentId')?.value || '';
      if (!fid) {
        if (orderErrorText) orderErrorText.textContent = 'Выберите филамент, чтобы продолжить.';
        openModal(errorModal);
        return false;
      }
    }

    return true;
  };

  // navigation
  btnNext?.addEventListener('click', () => {
    if (!validateCurrent()) return;
    idx++;
    showStage();
  });

  const goPrev = () => { idx = Math.max(0, idx - 1); showStage(); };
  btnPrevTop?.addEventListener('click', goPrev);
  btnPrevBottom?.addEventListener('click', goPrev);

  // on serviceType change, reset wizard
  form.querySelectorAll('input[name="serviceType"]').forEach(r => {
    r.addEventListener('change', () => { idx = 0; showStage(); });
  });

  // submit: send to server
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateCurrent()) return;

    const serviceType = getServiceType();

    const data = new FormData();
    data.append('serviceType', serviceType);
    data.append('clientContact', document.getElementById('clientContact')?.value || '');
    data.append('taskDesc', document.getElementById('taskDesc')?.value || '');

    data.append('filamentId', document.getElementById('selectedFilamentId')?.value || '');
    data.append('filamentName', document.getElementById('selectedFilamentName')?.value || '');

    const filesInput = document.getElementById('filesInput');
    if (filesInput && filesInput.files && filesInput.files.length) {
      [...filesInput.files].forEach((f, i) => data.append(`file_${i}`, f));
    }

    try {
      const r = await fetch('/api/order.php', { method: 'POST', body: data });
      const j = await r.json().catch(() => ({}));

      if (!r.ok || !j.ok) {
        if (orderErrorText) orderErrorText.textContent = 'Сервер не принял заказ. Попробуйте ещё раз.';
        openModal(errorModal);
        return;
      }

      // success
      form.reset();
      idx = 0;
      showStage();
      openModal(successModal);

    } catch (err) {
      if (orderErrorText) orderErrorText.textContent = 'Ошибка сети. Попробуйте ещё раз.';
      openModal(errorModal);
    }
  });

  showStage();
})();
