(function (Drupal, once) {
  'use strict';
  Drupal.behaviors.jeAiEditorial = {
    attach(context) {
      once('je-ai-editorial', 'form.je-ai-submission', context).forEach((form) => {
        const status = form.querySelector('.je-ai-progress');
        const generate = form.querySelector('[data-je-ai-generate]');
        if (generate && status) {
          status.addEventListener('cancel', (event) => event.preventDefault());
          generate.addEventListener('click', () => {
            if (!form.reportValidity()) return;
            status.hidden = false;
            if (!status.open) status.showModal();
            form.setAttribute('aria-busy', 'true');
            const accept = form.querySelector('[data-je-ai-accept]');
            if (accept) accept.disabled = true;
          });
        }
        if (generate && status) {
          new MutationObserver(() => {
            if (!generate.disabled && form.getAttribute('aria-busy') === 'true') {
              form.removeAttribute('aria-busy');
              status.close();
              status.hidden = true;
              const accept = form.querySelector('[data-je-ai-accept]');
              if (accept) accept.disabled = false;
            }
          }).observe(generate, { attributes: true, attributeFilter: ['disabled'] });
        }
        form.querySelectorAll('[data-je-ai-option]').forEach((input) => {
          const item = input.closest('.form-item');
          if (!item) return;
          item.classList.toggle('je-ai-option--enabled', input.checked);
          input.addEventListener('change', () => {
            item.classList.toggle('je-ai-option--enabled', input.checked);
            item.classList.remove('je-ai-option--changed');
            // Den kurzen Impuls bei jeder Änderung neu starten.
            void item.offsetWidth;
            item.classList.add('je-ai-option--changed');
          });
        });
      });
    }
  };
})(Drupal, once);
