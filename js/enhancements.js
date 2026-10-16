(function (Drupal, once, drupalSettings) {
  'use strict';
  const motionCleanup = new WeakMap();
  Drupal.behaviors.jeAiEnhancements = {
    attach(context) {
      const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
      const allowed = new Set(['ki-animation.json', 'ai-enabled-animation.json', 'success-animation.json', 'error-animation.json', 'release-animation.json']);
      once('je-ai-motion', '[data-je-lottie]', context).forEach((host) => {
        const file = host.dataset.jeLottie;
        if (!allowed.has(file) || !drupalSettings.jeAi?.animations) return;
        let player;
        let visible = false;
        const update = () => {
          if (!player) return;
          if (reduced.matches || !visible) player.pause();
          else player.play();
        };
        const observer = new IntersectionObserver((entries) => {
          visible = entries[0].isIntersecting;
          if (visible && !player) {
            player = document.createElement('lottie-player');
            player.setAttribute('src', drupalSettings.jeAi.animations + file);
            player.setAttribute('background', 'transparent');
            player.setAttribute('speed', '0.85');
            player.setAttribute('loop', '');
            player.setAttribute('aria-hidden', 'true');
            player.addEventListener('ready', update);
            host.append(player);
          }
          update();
        });
        observer.observe(host);
        reduced.addEventListener('change', update);
        motionCleanup.set(host, () => { observer.disconnect(); reduced.removeEventListener('change', update); player?.pause(); });
      });
      once('je-ai-release', 'dialog[data-je-release]', context).forEach((dialog) => {
        if (!dialog.showModal) return;
        dialog.showModal();
        dialog.addEventListener('cancel', (event) => {
          event.preventDefault();
          dialog.querySelector('form').requestSubmit();
        });
      });
      once('je-ai-tags', '[data-je-tags]', context).forEach((original) => {
        const pool = original.dataset.jeTagPool === '1';
        const separator = pool ? '\n' : ', ';
        const limit = pool ? 200 : 12;
        let tags = original.value.split(pool ? /[\r\n]+/ : ',').map((s) => s.trim()).filter(Boolean);
        const box = document.createElement('div');
        box.className = 'je-ai-tags';
        const list = document.createElement('div');
        list.className = 'je-ai-tags__list';
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 80;
        input.placeholder = Drupal.t('Schlagwort hinzufügen');
        input.setAttribute('aria-label', Drupal.t('Schlagwort hinzufügen'));
        const add = document.createElement('button');
        add.type = 'button';
        add.className = 'button button--small';
        add.textContent = Drupal.t('Hinzufügen');
        const help = document.createElement('div');
        help.setAttribute('role', 'status');
        help.className = 'je-ai-tags__hint';
        const editor = document.createElement('div');
        editor.className = 'je-ai-tags__editor';
        editor.append(input, add);
        box.append(list, editor, help);
        original.after(box);
        original.hidden = true;
        original.style.display = 'none';
        original.labels?.forEach((label) => label.setAttribute('for', input.id = original.id + '-chips'));
        const render = () => {
          list.replaceChildren();
          tags.forEach((tag, index) => {
            const chip = document.createElement('span');
            chip.className = 'je-ai-chip';
            const label = document.createElement('span');
            label.textContent = tag;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.setAttribute('aria-label', Drupal.t('Schlagwort entfernen: @tag', { '@tag': tag }));
            remove.addEventListener('click', () => { tags.splice(index, 1); render(); input.focus(); });
            chip.append(label, remove);
            list.append(chip);
          });
          original.value = tags.join(separator);
          original.dispatchEvent(new Event('input', { bubbles: true }));
        };
        const commit = () => {
          const incoming = input.value.split(/[,\r\n]+/).map((s) => s.trim()).filter(Boolean);
          if (!incoming.length) return;
          help.textContent = '';
          for (const tag of incoming) {
            if (/^\d{4}$/.test(tag)) { help.textContent = Drupal.t('Das Jahr wird bereits aus dem Beitragsdatum zugeordnet.'); continue; }
            if (tags.some((s) => s.toLocaleLowerCase() === tag.toLocaleLowerCase())) continue;
            if (tag.length > 80 || /[<>]/.test(tag)) { help.textContent = Drupal.t('Schlagwörter bitte ohne HTML und mit höchstens 80 Zeichen eingeben.'); continue; }
            if (tags.length >= limit) { help.textContent = pool ? Drupal.t('Höchstens 200 bevorzugte Schlagwörter.') : Drupal.t('Höchstens zwölf manuelle Schlagwörter.'); break; }
            tags.push(tag);
          }
          input.value = '';
          render();
        };
        input.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ',') { event.preventDefault(); commit(); }
        });
        input.addEventListener('blur', commit);
        add.addEventListener('click', commit);
        tags = [...new Map(tags.filter((tag) => !/^\d{4}$/.test(tag)).map((tag) => [tag.toLocaleLowerCase(), tag])).values()];
        render();
      });
      once('je-ai-year', '[data-je-year]', context).forEach((year) => {
        const date = year.closest('form').querySelector('input[type="date"]');
        if (!date) return;
        const update = () => {
          const value = /^\d{4}-/.test(date.value) ? date.value.slice(0, 4) : '—';
          year.textContent = Drupal.t('@year · Jahr aus Beitragsdatum – kein zusätzliches Schlagwort', { '@year': value });
        };
        date.addEventListener('change', update);
        date.addEventListener('input', update);
        update();
      });
      once('je-ai-tag-search', '[data-je-tag-search]', context).forEach((search) => {
        const choices = search.closest('details').querySelectorAll('input[type="checkbox"]');
        search.addEventListener('keydown', (event) => { if (event.key === 'Enter') event.preventDefault(); });
        const filter = () => {
          const term = search.value.trim().toLocaleLowerCase();
          choices.forEach((choice) => {
            const row = choice.closest('.form-item');
            if (row) row.hidden = !row.textContent.toLocaleLowerCase().includes(term);
          });
        };
        search.addEventListener('input', filter);
        filter();
      });
      once('je-ai-import-file', '[data-je-style-file]', context).forEach((file) => {
        file.addEventListener('change', async () => {
          const selected = file.files[0];
          const target = file.closest('form').querySelector('[data-je-style-json]');
          if (!selected || !target) return;
          if (selected.size > 512000) { file.value = ''; window.alert(Drupal.t('Bitte höchstens 500 KB importieren.')); return; }
          target.value = await selected.text();
          target.dispatchEvent(new Event('input', { bubbles: true }));
        });
      });
    },
    detach(context, settings, trigger) {
      if (trigger !== 'unload') return;
      once.filter('je-ai-motion', '[data-je-lottie]', context).forEach((host) => { motionCleanup.get(host)?.(); motionCleanup.delete(host); });
    }
  };
})(Drupal, once, drupalSettings);
