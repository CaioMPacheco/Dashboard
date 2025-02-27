// theme.js
const themeToggle = document.getElementById('themeToggle');
const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');

// Função para atualizar o ícone do botão
function updateThemeIcon(isDark) {
    const icon = themeToggle.querySelector('i');
    icon.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
}

// Função para definir o tema
function setTheme(theme) {
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    updateThemeIcon(theme === 'dark');
}

// Inicialização do tema
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {// theme.js - continuação
        setTheme(savedTheme);
    } else {
        // Se não houver tema salvo, use a preferência do sistema
        const systemTheme = prefersDarkScheme.matches ? 'dark' : 'light';
        setTheme(systemTheme);
    }
}

// Listener para o botão de tema
themeToggle.addEventListener('click', () => {
    const currentTheme = document.body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
});

// Listener para mudanças nas preferências do sistema
prefersDarkScheme.addListener((e) => {
    if (!localStorage.getItem('theme')) {
        setTheme(e.matches ? 'dark' : 'light');
    }
});

// Inicializar tema ao carregar a página
initializeTheme();