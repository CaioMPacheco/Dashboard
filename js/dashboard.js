// Elementos do DOM
const sidebar = document.getElementById('sidebar');
const menuToggle = document.getElementById('menuToggle');
const closeSidebar = document.getElementById('closeSidebar');
const themeToggle = document.getElementById('themeToggle');

// Gerenciamento do tema
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = themeToggle.querySelector('i');
    icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
}

// Gerenciamento da sidebar
function toggleSidebar() {
    sidebar.classList.toggle('open');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : 'auto';
}

// Animações para elementos do dashboard
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '20px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observar cards e gráficos para animação
    document.querySelectorAll('.stat-card, .chart-card, .metric-card').forEach(el => {
        observer.observe(el);
    });
}



// Handlers de eventos
function initEventListeners() {
    menuToggle.addEventListener('click', toggleSidebar);
    closeSidebar.addEventListener('click', toggleSidebar);
    themeToggle.addEventListener('click', toggleTheme);

    // Fechar sidebar ao clicar fora
    document.addEventListener('click', (e) => {
        if (sidebar.classList.contains('open') && 
            !sidebar.contains(e.target) && 
            !menuToggle.contains(e.target)) {
            toggleSidebar();
        }
    });

    // Atualizar layout em resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    });
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initAnimations();
    initEventListeners();
});

// Preparar áreas para dados dinâmicos
function setupDataContainers() {
    // IDs dos elementos que receberão dados via PHP/JavaScript
    const dynamicElements = [
        'totalRevenue',
        'onlineRevenue',
        'expenses',
        'totalUsers',
        'salesMetric',
        'reviewsMetric',
        'visitorsMetric'
    ];

    // Adicionar classes para loading state
    dynamicElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.classList.add('loading');
        }
    });
}

document.getElementById('totalRevenue').textContent = 'R$ 15000.75';
document.getElementById('onlineRevenue').textContent = 'R$ 8000.50';


// Função para atualizar elementos quando os dados estiverem disponíveis
function updateDashboardData(data) {
    const elements = {
        totalRevenue: (value) => `R$ ${value.toFixed(2)}`,
        onlineRevenue: (value) => `R$ ${value.toFixed(2)}`,
        expenses: (value) => `R$ ${value.toFixed(2)}`,
        totalUsers: (value) => value.toString(),
        salesMetric: (value) => value.toString(),
        reviewsMetric: (value) => value.toString(),
        visitorsMetric: (value) => value.toString()
    };

    Object.entries(data).forEach(([key, value]) => {
        const element = document.getElementById(key);
        if (element && elements[key]) {
            element.textContent = elements[key](value);
            element.classList.remove('loading');
        }
    });
}

