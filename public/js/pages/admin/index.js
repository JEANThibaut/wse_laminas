document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.tab-link').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();

      document.querySelectorAll('.tab-link').forEach(el => el.classList.remove('active'));
      link.classList.add('active');
      const tab = link.dataset.tab;
      loadTab(tab);
    });
  });

  const firstTab = document.querySelector('.tab-link');
  if (firstTab) firstTab.click();
});



function loadTab(tabName) {
  const container = document.getElementById('tabContent');
  container.innerHTML = 'Chargement...';

  // Supprime l’ancien script
  const existingScript = document.getElementById('tab-script');
  if (existingScript) existingScript.remove();

  // Charge la vue
  fetch(`admin/${tabName}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(res => res.text())
    .then(html => {
      container.innerHTML = html;

      const script = document.createElement('script');
      script.id = 'tab-script';
      script.src = `/js/pages/admin/${tabName}.js`;
      document.body.appendChild(script);
    })
    .catch(err => {
      container.innerHTML = `<div class="text-danger">Erreur : ${err}</div>`;
    });
}

