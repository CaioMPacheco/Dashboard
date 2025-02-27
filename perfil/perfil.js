// Elementos do DOM
const sidebar = document.getElementById('sidebar');
const menuToggle = document.getElementById('menuToggle');
const closeSidebar = document.getElementById('closeSidebar');
const themeToggle = document.getElementById('themeToggle');
const profileForm = document.getElementById('profileForm');
const imageInput = document.getElementById('imageInput');
const profileImage = document.getElementById('profileImage');
const passwordInputs = document.querySelectorAll('input[type="password"]');

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

// Gerenciamento de imagem de perfil
function handleImageUpload(event) {
    const file = event.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            profileImage.src = e.target.result;
            // Aqui você pode adicionar código para enviar a imagem para o servidor
        };
        reader.readAsDataURL(file);
    } else {
        alert('Por favor, selecione uma imagem válida.');
    }
}

// Validação de formulário
function validateForm(event) {
    event.preventDefault();
    
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Validação de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('email', 'Por favor, insira um email válido');
        return;
    }

    // Validação de senha
    if (newPassword) {
        if (!currentPassword) {
            showError('currentPassword', 'A senha atual é necessária para alterar a senha');
            return;
        }
        if (newPassword.length < 8) {
            showError('newPassword', 'A nova senha deve ter pelo menos 8 caracteres');
            return;
        }
        if (newPassword !== confirmPassword) {
            showError('confirmPassword', 'As senhas não coincidem');
            return;
        }
    }

    // Se tudo estiver válido, você pode enviar os dados para o servidor
    saveProfile({
        name,
        email,
        currentPassword,
        newPassword
    });
}

function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    // Remove qualquer mensagem de erro existente
    const existingError = field.parentElement.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    field.parentElement.appendChild(errorDiv);
    field.classList.add('error');
    
    // Remove a mensagem de erro após 3 segundos
    setTimeout(() => {
        errorDiv.remove();
        field.classList.remove('error');
    }, 3000);
}

// Função simulada de salvamento
async function saveProfile(data) {
    try {
        // Simula uma chamada de API
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Exibe mensagem de sucesso
        const successMessage = document.createElement('div');
        successMessage.className = 'success-message';
        successMessage.textContent = 'Perfil atualizado com sucesso!';
        profileForm.insertAdjacentElement('beforebegin', successMessage);
        
        setTimeout(() => successMessage.remove(), 3000);
        
    } catch (error) {
        console.error('Erro ao salvar perfil:', error);
        showError('form', 'Erro ao salvar as alterações. Tente novamente.');
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    
    themeToggle.addEventListener('click', toggleTheme);
    menuToggle.addEventListener('click', toggleSidebar);
    closeSidebar.addEventListener('click', toggleSidebar);
    imageInput.addEventListener('change', handleImageUpload);
    profileForm.addEventListener('submit', validateForm);

    // Toggle de visibilidade da senha
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', () => {
            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    });

    // Limpa mensagens de erro ao digitar
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('input', () => {
            const errorMessage = input.parentElement.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
                input.classList.remove('error');
            }
        });
    });
});

// Prevenção de envio acidental do formulário
window.addEventListener('beforeunload', (event) => {
    const formData = new FormData(profileForm);
    const hasChanges = Array.from(formData.values()).some(value => value !== '');
    
    if (hasChanges) {
        event.preventDefault();
        event.returnValue = '';
    }
});

