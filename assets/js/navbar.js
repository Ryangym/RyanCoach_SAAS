const menuToggle = document.getElementById('menu-toggle');
const mobileNav = document.getElementById('mobile-nav');
const mobileNavbar = document.querySelector('.mobile-navbar');
const desktopNavbar = document.querySelector('.navbar');

let lastScrollTop = 0;

// Animação do botão hambúrguer
menuToggle?.addEventListener('click', () => {
  const isActive = mobileNav.classList.toggle('active');
  menuToggle.classList.toggle('active');
  document.body.classList.toggle('noscroll', isActive);
});

// Fecha o menu mobile ao clicar em qualquer link
const navLinks = mobileNav.querySelectorAll('a');

navLinks.forEach(link => {
  link.addEventListener('click', () => {
    mobileNav.classList.remove('active');
    menuToggle.classList.remove('active');
    document.body.classList.remove('noscroll');
  });
});

// Esconde/mostra navbar com scroll
window.addEventListener('scroll', () => {
  const scrollTop = window.scrollY || document.documentElement.scrollTop;

  // Esconde se rolar para baixo, mostra se rolar para cima
  const shouldHide = scrollTop > lastScrollTop;

  if (window.innerWidth > 768 && desktopNavbar) {
    desktopNavbar.classList.toggle('hidden', shouldHide);
  }

  if (window.innerWidth <= 768 && mobileNavbar && !mobileNav.classList.contains('active')) {
    mobileNavbar.classList.toggle('hidden', shouldHide);
  }

  lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
});


// --- Script do Dropdown de Perfil (Modelo Novo) ---
document.addEventListener('DOMContentLoaded', () => {
    const profileIcon = document.getElementById('userMenuToggle');
    const profileMenu = document.getElementById('profileMenu');

    if (profileIcon && profileMenu) {

        // 1. Abrir/Fechar ao clicar no ícone
        profileIcon.addEventListener('click', (e) => {
            e.stopPropagation(); // Impede que o clique feche o menu imediatamente
            profileMenu.classList.toggle('show');
        });

        // 2. Fechar ao clicar em qualquer lugar fora do menu
        window.addEventListener('click', (e) => {
            if (profileMenu.classList.contains('show')) {
                profileMenu.classList.remove('show');
            }
        });

        // 3. Impedir que cliques dentro do menu fechem ele
        profileMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
});