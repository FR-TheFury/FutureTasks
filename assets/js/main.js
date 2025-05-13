
// Script principal pour FutureTasks
document.addEventListener('DOMContentLoaded', function() {
  // Gestion du toggle du sidebar
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');
  
  if (sidebarToggle && sidebar && mainContent) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });
  }
  
  // Animation d'entrée pour les cartes de statistiques
  const statsCards = document.querySelectorAll('.stats-cards .futuristic-card');
  if (statsCards.length > 0) {
    statsCards.forEach((card, index) => {
      setTimeout(() => {
        card.style.animation = 'fadeIn 0.5s ease forwards';
      }, 200 * index);
    });
  }
  
  // Animation pour les rangées de tableau
  const tableRows = document.querySelectorAll('.futuristic-table tbody tr');
  if (tableRows.length > 0) {
    tableRows.forEach((row, index) => {
      row.style.opacity = '0';
      row.style.transform = 'translateY(20px)';
      
      setTimeout(() => {
        row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        row.style.opacity = '1';
        row.style.transform = 'translateY(0)';
      }, 100 * index);
    });
  }
  
  // Validation des formulaires
  const forms = document.querySelectorAll('.needs-validation');
  if (forms.length > 0) {
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }
  
  // Effet focus pour les inputs
  const futuristicInputs = document.querySelectorAll('.futuristic-input, .futuristic-textarea');
  if (futuristicInputs.length > 0) {
    futuristicInputs.forEach(input => {
      input.addEventListener('focus', () => {
        const line = input.nextElementSibling;
        if (line && line.classList.contains('input-line')) {
          line.style.width = '100%';
        }
      });
      
      input.addEventListener('blur', () => {
        const line = input.nextElementSibling;
        if (line && line.classList.contains('input-line')) {
          line.style.width = '0';
        }
      });
    });
  }
  
  // Fermez automatiquement les alertes après 5 secondes
  const alerts = document.querySelectorAll('.alert:not(.alert-dismissible)');
  if (alerts.length > 0) {
    alerts.forEach(alert => {
      setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => {
          alert.remove();
        }, 500);
      }, 5000);
    });
  }

  // Correction pour les modales d'édition
  // S'assure que les modales s'affichent correctement au-dessus des autres éléments
  const modals = document.querySelectorAll('.modal');
  if (modals.length > 0) {
    modals.forEach(modal => {
      // S'assurer que la modal a un z-index élevé
      modal.style.zIndex = '1050';
      
      // S'assurer que la modal est visible quand elle est ouverte
      modal.addEventListener('shown.bs.modal', function() {
        this.style.display = 'block';
        document.body.classList.add('modal-open');
        const modalBackdrop = document.querySelector('.modal-backdrop');
        if (modalBackdrop) {
          modalBackdrop.style.zIndex = '1040';
        }
      });
    });
  }
  
  // Correction spécifique pour les formulaires d'édition en pop-up
  const editForms = document.querySelectorAll('.edit-form, .futuristic-form');
  if (editForms.length > 0) {
    editForms.forEach(form => {
      // S'assurer que le formulaire est bien visible
      form.style.position = 'relative';
      form.style.zIndex = '1000';
      
      // Gérer les conteneurs parents qui pourraient masquer le formulaire
      const parentPanel = form.closest('.futuristic-panel');
      if (parentPanel) {
        parentPanel.style.overflow = 'visible';
      }
    });
  }
  
  // Correction pour les conteneurs de formulaires
  const formContainers = document.querySelectorAll('.futuristic-panel');
  if (formContainers.length > 0) {
    formContainers.forEach(container => {
      container.style.overflow = 'visible';
    });
  }
});
