document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('[data-install-form]');
  forms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const action = form.getAttribute('data-install-action') || 'install';
      const versionInput = form.querySelector('input[name="version"]');
      const version = versionInput && versionInput.value.trim()
        ? `version "${versionInput.value.trim()}"`
        : 'the default version';
      const verb = action === 'downgrade' ? 'downgrade' : 'upgrade';
      const message = `This will create a database backup and then ${verb} to ${version}. Continue?`;
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
});
