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

    fetch(`/admin-${tabName}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.text())
    .then(html => {
        container.innerHTML = html;

        // if (tabName === 'games') {
        //     const script = document.createElement('script');
        //     script.src = '/js/pages/admin-game-index.js';
        //     document.body.appendChild(script);
        // }
        if (tabName === 'users') {
            const script = document.createElement('script');
            script.src = '/js/pages/admin-users-index.js';
            document.body.appendChild(script);
        }
    })
    .catch(err => {
        container.innerHTML = `<div class="text-danger">Erreur : ${err}</div>`;
    });
}
