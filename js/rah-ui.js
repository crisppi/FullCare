(function (w) {
  const $ = w.jQuery;
  const R = w.RAH;

  // Máscara dinheiro
  function applyMask(ctx) {
    if (!$ || !$.fn.maskMoney || $.fn.maskMoney.__stub__ === true) return;
    $(ctx || document).find('.dinheiro').maskMoney({
      thousands: '.', decimal: ',', allowZero: true, precision: 2
    });
  }

  function setupBlockCollapse() {
    function rahCollapseEvent(name, panel) {
      const ev = new Event(name, { bubbles: true, cancelable: false });
      panel.dispatchEvent(ev);
    }

    function setButtonState(panel, expanded) {
      const id = panel && panel.id;
      if (!id) return;
      document.querySelectorAll('.block-toggle[data-bs-target="#' + id + '"]').forEach((btn) => {
        btn.classList.toggle('collapsed', !expanded);
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      });
    }

    document.querySelectorAll('.block-toggle[data-bs-target]').forEach((btn) => {
      btn.removeAttribute('data-bs-toggle');
      const selector = btn.getAttribute('data-bs-target');
      const panel = selector ? document.querySelector(selector) : null;
      if (!panel) return;
      panel.classList.remove('collapsing');
      panel.style.display = panel.classList.contains('show') ? 'block' : 'none';
      setButtonState(panel, panel.classList.contains('show'));
    });

    document.addEventListener('click', function (ev) {
      const btn = ev.target && ev.target.closest ? ev.target.closest('.block-toggle[data-bs-target]') : null;
      if (!btn) return;

      const selector = btn.getAttribute('data-bs-target');
      const panel = selector ? document.querySelector(selector) : null;
      if (!panel) return;

      ev.preventDefault();
      ev.stopImmediatePropagation();

      const isOpen = panel.classList.contains('show');
      panel.classList.remove('collapsing');
      if (isOpen) {
        rahCollapseEvent('hide.bs.collapse', panel);
        panel.classList.remove('show');
        panel.style.display = 'none';
        setButtonState(panel, false);
        rahCollapseEvent('hidden.bs.collapse', panel);
      } else {
        rahCollapseEvent('show.bs.collapse', panel);
        panel.style.display = 'block';
        panel.classList.add('show');
        setButtonState(panel, true);
        rahCollapseEvent('shown.bs.collapse', panel);
      }
    }, true);

    document.addEventListener('shown.bs.collapse', (ev) => setButtonState(ev.target, true));
    document.addEventListener('hidden.bs.collapse', (ev) => setButtonState(ev.target, false));
  }

  // Espelho “Período e Totais”
  (function setupPeriodMirror() {
    function syncPeriodTotals(tCob, tLib) {
      const $desc  = $('#desconto_valor_cap');
      let d = 0;
      if ($desc.length) d = R.moneyToFloat($desc.val());
      const vFinal = Math.max(0, tLib - d);

      const $inpApr = $('[name="valor_apresentado_capeante"]').first();
      const $inpFin = $('[name="valor_final_capeante"]').first();

      function setMoney($inp, valorNum) {
        if (!$inp.length) return;
        if ($.fn.maskMoney && $.fn.maskMoney.__stub__ !== true) $inp.maskMoney('mask', Number(valorNum));
        else $inp.val(R.floatToMoney(valorNum)).trigger('change');
      }
      setMoney($inpApr, tCob);
      setMoney($inpFin, vFinal);

      $('.pill-val-apr').text(R.floatToMoney(tCob));
      $('.pill-val-fin').text(R.floatToMoney(vFinal));
      setGlosaField(getGlosaTotal());
    }
    w.RAHSync = { syncPeriodTotals };

    function sumAll() {
      let tCob = 0, tLib = 0;
      $('.tuss-row').each(function () {
        const $r = $(this);
        tCob += R.moneyToFloat($r.find('.rah-cobrado').val());
        tLib += R.moneyToFloat($r.find('.rah-liberado').val());
      });
      const $desc = $('#desconto_valor_cap');
      let d = 0;
      if ($desc.length) d = R.moneyToFloat($desc.val());
      const vFinal = Math.max(0, tLib - d);
      return { tCob, vFinal };
    }

    function mirrorNow() {
      const tot = sumAll();
      const $apr = $('[name="valor_apresentado_capeante"]').first();
      const $fin = $('[name="valor_final_capeante"]').first();

      if ($.fn.maskMoney && $.fn.maskMoney.__stub__ !== true) {
        $apr.maskMoney('mask', Number(tot.tCob));
        $fin.maskMoney('mask', Number(tot.vFinal));
      } else {
        $apr.val(R.floatToMoney(tot.tCob));
        $fin.val(R.floatToMoney(tot.vFinal));
      }
      $('.pill-val-apr').text(R.floatToMoney(tot.tCob));
      $('.pill-val-fin').text(R.floatToMoney(tot.vFinal));
      setGlosaField(getGlosaTotal());
    }
    function getGlosaTotal() {
      let sum = 0;
      $('.rah-glosado').each(function () {
        sum += R.moneyToFloat($(this).val());
      });
      return sum;
    }

    function setGlosaField(valor) {
      const $field = $('#inp_val_glosa');
      if (!$field.length) return;
      if ($.fn.maskMoney && $.fn.maskMoney.__stub__ !== true) {
        $field.maskMoney('mask', Number(valor));
      } else {
        $field.val(R.floatToMoney(valor));
      }
    }

    $(function () {
      $(document).on('input change keyup', '.rah-cobrado, .rah-glosado, #desconto_valor_cap', mirrorNow);
      $(document).on('input change keyup', '.rah-glosado', function () {
        setGlosaField(getGlosaTotal());
      });
      document.addEventListener('shown.bs.collapse', function (ev) {
        applyMask(document);
        mirrorNow();
        const $blk = $(ev.target).closest('.block');
        if ($blk.length) {
          applyMask($blk.get(0));
          $blk.find('.rah-cobrado,.rah-glosado').first().trigger('input');
        }
      });
      mirrorNow();
      setTimeout(mirrorNow, 80);
    });
  })();

  // Cadastro Central: habilita/desabilita selects + pill
  function setupCadastroCentral() {
    function setEnabled(el, on) { if (!el) return; el.disabled = !on; if (!on) el.value = ""; }
    function updateUI() {
      const on = (document.getElementById('cadastro_central_cap')?.value || 'n') === 's';
      setEnabled(document.getElementById('cad_central_med_id'), on);
      setEnabled(document.getElementById('cad_central_enf_id'), on);
      setEnabled(document.getElementById('cad_central_adm_id'), on);
      const pill = document.getElementById('cc-pill');
      if (pill) pill.textContent = on ? 'Ativo' : 'Inativo';
    }
    document.addEventListener('change', (e) => {
      if (e.target && e.target.id === 'cadastro_central_cap') updateUI();
    });
    document.addEventListener('DOMContentLoaded', updateUI);
  }

  // Hidrata selects a partir dos hidden
  function hydrateSelects() {
    function setSelectByValue(sel, value, fallback) {
      if (!sel || !value || value === "0") return;
      let opt = sel.querySelector('option[value="' + value + '"]');
      if (!opt) {
        opt = document.createElement('option');
        opt.value = value;
        opt.textContent = fallback || ('Selecionado (ID ' + value + ')');
        sel.insertBefore(opt, sel.firstChild);
      }
      sel.value = value;
    }
    function fireChange(el) {
      if (!el) return;
      try { el.dispatchEvent(new Event('change', { bubbles:true })); }
      catch(e){ const evt=document.createEvent('HTMLEvents'); evt.initEvent('change', true, false); el.dispatchEvent(evt); }
    }
    function hydrate() {
      const selCentral = document.getElementById('cadastro_central_cap');
      const selMed    = document.getElementById('cad_central_med_id');
      const selEnf    = document.getElementById('cad_central_enf_id');
      const selAdm    = document.getElementById('cad_central_adm_id');

      const fkMed = (document.getElementById('fk_id_aud_med')||{}).value || "";
      const fkEnf = (document.getElementById('fk_id_aud_enf')||{}).value || "";
      const fkAdm = (document.getElementById('fk_id_aud_adm')||{}).value || "";

      if (selCentral && (fkMed && fkMed!=='0' || fkEnf && fkEnf!=='0' || fkAdm && fkAdm!=='0')) {
        selCentral.value = 's'; fireChange(selCentral);
      }
      setSelectByValue(selMed, fkMed, 'Médico selecionado (ID ' + fkMed + ')');
      setSelectByValue(selEnf, fkEnf, 'Enfermeiro(a) selecionado(a) (ID ' + fkEnf + ')');
      setSelectByValue(selAdm, fkAdm, 'Administrativo selecionado (ID ' + fkAdm + ')');

      fireChange(selMed); fireChange(selEnf); fireChange(selAdm); fireChange(selCentral);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', hydrate);
    else hydrate();
  }

  // Boot
  $(function () {
    applyMask(document);
    setupBlockCollapse();
    setupCadastroCentral();
    hydrateSelects();
  });

  // expõe util para quando inserir linhas dinamicamente
  w.RAHUI = { applyMask };
})(window);
