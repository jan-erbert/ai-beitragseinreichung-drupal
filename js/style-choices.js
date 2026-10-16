(function (Drupal, once, drupalSettings) {
  'use strict';
  const cleanups = new WeakMap();
  Drupal.behaviors.jeAiStyleChoices = {
    attach(context) {
      once('je-ai-icon-choice', 'select[data-je-icon-select]', context).forEach((select) => {
        const icons = drupalSettings.jeAi?.choiceIcons || {};
        const groups = JSON.parse(select.dataset.jeGroupIcons || '{}');
        const wrapper = document.createElement('div');
        wrapper.className = 'je-ai-choice';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = select.id + '-trigger';
        trigger.className = 'je-ai-choice__trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        const title = select.labels?.[0]?.textContent.trim() || Drupal.t('Beitragsart');
        const list = document.createElement('div');
        list.id = select.id + '-options';
        list.className = 'je-ai-choice__list';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', title);
        list.hidden = true;
        trigger.setAttribute('aria-controls', list.id);
        const error = document.createElement('p');
        error.className = 'je-ai-choice__error';
        error.id = select.id + '-error';
        error.setAttribute('role', 'alert');
        error.hidden = true;
        trigger.setAttribute('aria-required', String(select.required));
        const invalid = (event) => {
          event.preventDefault();
          error.textContent = Drupal.t('Bitte eine Beitragsart auswählen.');
          error.hidden = false;
          trigger.setAttribute('aria-invalid', 'true');
          trigger.setAttribute('aria-describedby', error.id);
          if (select.form?.querySelector(':invalid') === select) trigger.focus();
        };
        select.addEventListener('invalid', invalid);
        const options = [...select.options];
        const buttons = [];
        const content = (option) => {
          const span = document.createElement('span');
          span.className = 'je-ai-group-label';
          const icon = groups[option.value] || option.value;
          if (icons[icon]) {
            const image = document.createElement('img');
            image.src = icons[icon];
            image.width = 24;
            image.height = 24;
            image.alt = '';
            span.append(image);
          }
          const text = document.createElement('span');
          text.textContent = option.textContent;
          span.append(text);
          return span;
        };
        const close = (focus) => {
          list.hidden = true;
          trigger.setAttribute('aria-expanded', 'false');
          if (focus) trigger.focus();
        };
        const update = () => {
          const selected = options.find((option) => option.value === select.value) || options[0];
          trigger.replaceChildren(content(selected));
          trigger.setAttribute('aria-label', title + ': ' + selected.textContent);
          trigger.disabled = select.disabled;
          if (select.validity.valid) {
            error.hidden = true;
            trigger.removeAttribute('aria-invalid');
            trigger.removeAttribute('aria-describedby');
          }
          buttons.forEach((button, index) => button.setAttribute('aria-selected', String(options[index].value === select.value)));
          // Die Gruppenkopfzeile folgt derselben Symbolauswahl.
          if (!select.dataset.jeGroupIcons && icons[select.value]) {
            const summary = select.closest('details')?.querySelector(':scope > summary .je-ai-group-label img');
            if (summary) summary.src = icons[select.value];
          }
        };
        options.forEach((option) => {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'je-ai-choice__option';
          button.setAttribute('role', 'option');
          button.tabIndex = -1;
          button.disabled = option.disabled;
          button.append(content(option));
          button.addEventListener('click', () => {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close(true);
          });
          buttons.push(button);
          list.append(button);
        });
        const open = () => {
          list.hidden = false;
          trigger.setAttribute('aria-expanded', 'true');
          (buttons.find((button) => button.getAttribute('aria-selected') === 'true') || buttons[0])?.focus();
        };
        trigger.addEventListener('click', () => list.hidden ? open() : close(false));
        wrapper.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') { close(true); event.preventDefault(); return; }
          if (event.key === 'Tab') { close(false); return; }
          const navigation = ['ArrowDown', 'ArrowUp', 'Home', 'End'];
          if (navigation.includes(event.key)) {
            event.preventDefault();
            if (list.hidden) { open(); return; }
            let index = buttons.indexOf(document.activeElement);
            if (event.key === 'Home') index = 0;
            else if (event.key === 'End') index = buttons.length - 1;
            else index = (index + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length;
            buttons[index]?.focus();
          }
          else if (!list.hidden && event.key.length === 1 && !event.ctrlKey && !event.metaKey && event.key !== ' ') {
            const start = buttons.indexOf(document.activeElement);
            for (let step = 1; step <= buttons.length; step++) {
              const index = (start + step) % buttons.length;
              if (options[index].textContent.toLocaleLowerCase().startsWith(event.key.toLocaleLowerCase())) { buttons[index].focus(); break; }
            }
          }
        });
        wrapper.addEventListener('focusout', (event) => { if (!wrapper.contains(event.relatedTarget)) close(false); });
        const outside = (event) => { if (!wrapper.contains(event.target)) close(false); };
        document.addEventListener('pointerdown', outside);
        select.addEventListener('change', update);
        wrapper.append(trigger, list, error);
        select.after(wrapper);
        update();
        select.hidden = true;
        cleanups.set(select, () => { document.removeEventListener('pointerdown', outside); select.removeEventListener('change', update); select.removeEventListener('invalid', invalid); wrapper.remove(); select.hidden = false; });
      });
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') once.remove('je-ai-icon-choice', 'select[data-je-icon-select]', context).forEach((select) => { cleanups.get(select)?.(); cleanups.delete(select); });
    },
  };
})(Drupal, once, drupalSettings);
