<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Login - Ryan Coach</title>
    
    <link rel="stylesheet" href="assets/css/login.css">
    <?php include 'includes/head_main.php'; ?>
</head>
<body>

    <a href="index.php" class="logo-link">
        <h2 class="logo">Ryan Coach</h2>
    </a>

    <div class="container" id="container">

        <div class="form-container sign-up-container">
            <form action="actions/auth_register.php" method="POST">
                <h1>Criar Conta</h1>
                <span>Use seu email para se cadastrar</span>
                <div class="input-group">
                    <input type="text" id="reg-name" name="nome" placeholder=" " required />
                    <label for="reg-name">Nome</label>
                </div>
                <div class="input-group">
                    <input type="email" id="reg-email" name="email" placeholder=" " required />
                    <label for="reg-email">Email</label>
                </div>
                <div class="input-group">
                    <input type="tel" id="reg-phone" name="telefone" placeholder=" " maxlength="15" required />
                    <label for="reg-phone">Telefone (Whatsapp)</label>
                </div>
                
                <div class="input-group">
                    <input type="password" id="reg-pass" name="senha" placeholder=" " required>
                    <label for="reg-pass">Senha</label>
                    <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('reg-pass', this)"></i>
                </div>

                <div class="input-group">
                    <input type="text" name="codigo_indicacao" id="input-cupom" placeholder=" " style="color: var(--gold); font-weight: bold;">
                    <label for="input-cupom">Código de convite (opcional)</label>
                </div>

                <button type="submit" class="btn-submit">Cadastrar</button>
                
                <p class="form-bottom-text mobile-only">
                    Já tem uma conta? <a href="#" class="form-link" id="signInMobile">Faça Login</a>
                </p>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <a class="return" href="index.php"><i class="fa-solid fa-arrow-left"></i></a>
            <form id="formLogin" onsubmit="fazerLogin(event)">
                <input type="hidden" name="tipo_login" value="aluno">
                <h1>Entrar</h1>
                <span>Use sua conta</span>
                <div class="input-group">
                    <input type="email" id="login-email" name="email" placeholder=" " required />
                    <label for="login-email">Email</label>
                </div>
                
                <div class="input-group">
                    <input type="password" id="login-pass" name="senha" placeholder=" " required />
                    <label for="login-pass">Senha</label>
                    <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('login-pass', this)"></i>
                </div>

                <a href="#" class="form-link forgot-pass">Esqueceu sua senha?</a>
                <button type="submit" class="btn-submit">Entrar</button>

                <p class="form-bottom-text mobile-only">
                    Não tem uma conta? <a href="#" class="form-link" id="signUpMobile">Cadastre-se</a>
                </p>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <video autoplay loop muted playsinline class="overlay-video">
                    <source src="assets/videos/Man_Lifting_Weights_in_Gym.mp4" type="video/mp4">
                    Seu navegador não suporta vídeos.
                </video>
                <div class="overlay-panel overlay-left">
                    <h2>Bem-vindo de Volta!</h2>
                    <p>Para se manter conectado conosco, por favor, faça o login com suas informações pessoais</p>
                    <button class="btn-ghost" id="signIn">Entrar</button>
                </div>

                <div class="overlay-panel overlay-right">
                    <h2>Olá, Amigo!</h2>
                    <p>Entre com seus dados e comece sua jornada de transformação conosco</p>
                    <button class="btn-ghost" id="signUp">Cadastre-se</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ---------------------------------------------------------------
        // 0. FUNÇÃO PARA ALTERNAR VISIBILIDADE DA SENHA
        // ---------------------------------------------------------------
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash'); // Olho cortado
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye'); // Olho normal
            }
        }

        // ---------------------------------------------------------------
        // 1. LÓGICA DE ALTERNÂNCIA DE TELAS (SIGN IN / SIGN UP)
        // ---------------------------------------------------------------
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('container');
            
            // Botões do Desktop (Overlay)
            const signUpButton = document.getElementById('signUp');
            const signInButton = document.getElementById('signIn');

            // Links de texto do Mobile
            const signUpLinkMobile = document.getElementById('signUpMobile');
            const signInLinkMobile = document.getElementById('signInMobile');

            // Funções de Troca
            const showSignUp = () => {
                if(container) container.classList.add('active');
            };

            const showSignIn = () => {
                if(container) container.classList.remove('active');
            };

            // --- Listeners Desktop ---
            if (signUpButton) {
                signUpButton.addEventListener('click', () => showSignUp());
            }
            if (signInButton) {
                signInButton.addEventListener('click', () => showSignIn());
            }

            // --- Listeners Mobile ---
            if (signUpLinkMobile) {
                signUpLinkMobile.addEventListener('click', (e) => {
                    e.preventDefault(); // Evita recarregar a página
                    showSignUp();
                });
            }

            if (signInLinkMobile) {
                signInLinkMobile.addEventListener('click', (e) => {
                    e.preventDefault(); // Evita recarregar a página
                    showSignIn();
                });
            }
        });

        // ---------------------------------------------------------------
        // 2. FUNÇÃO DE LOGIN AJAX
        // ---------------------------------------------------------------
        function fazerLogin(event) {
            event.preventDefault(); // Impede o reload da página

            const form = document.getElementById('formLogin');
            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            
            // Efeito visual no botão
            const textoOriginal = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Entrando...';
            btn.disabled = true;

            fetch('actions/auth_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                    btn.innerHTML = textoOriginal;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro de conexão com o servidor.');
                btn.innerHTML = textoOriginal;
                btn.disabled = false;
            });
        }

        // ---------------------------------------------------------------
        // 3. PREENCHER CÓDIGO DE CUPOM VIA URL
        // ---------------------------------------------------------------
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const refCode = urlParams.get('ref'); // Pega ?ref=CODIGO
            
            if (refCode) {
                // Tenta achar o input
                const input = document.getElementById('input-cupom'); 
                
                // Se achou, preenche e abre a tela de cadastro
                if (input) {
                    input.value = refCode;
                    input.readOnly = true; // Trava para não editar
                    
                    // Força a abertura do painel de cadastro (se estiver usando aquele layout deslizante)
                    const container = document.getElementById('container');
                    if(container) container.classList.add('active');
                }
            }
        });

        // ---------------------------------------------------------------
        // 4. MÁSCARA E VALIDAÇÃO DO TELEFONE
        // ---------------------------------------------------------------
        document.addEventListener("DOMContentLoaded", function() {
            const phoneInput = document.getElementById('reg-phone');

            // SÓ RODA SE O CAMPO EXISTIR NA TELA
            if (phoneInput) { 
                const form = phoneInput.closest('form'); 

                // Máscara Automática
                phoneInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, ''); 
                    
                    if (value.length > 11) value = value.slice(0, 11);

                    if (value.length > 2) {
                        value = `(${value.substring(0, 2)}) ${value.substring(2)}`;
                    }
                    
                    if (value.length > 7) {
                        value = `${value.substring(0, 10)} ${value.substring(10)}`;
                    }

                    e.target.value = value;
                });

                // Validação ao Enviar
                if (form) { 
                    form.addEventListener('submit', function(e) {
                        const rawValue = phoneInput.value.replace(/\D/g, ''); 

                        if (rawValue.length < 11) {
                            e.preventDefault(); 
                            
                            alert("Por favor, digite o número completo com DDD (Ex: 11 91234 5678).");
                            
                            phoneInput.focus();
                            phoneInput.style.borderBottom = "2px solid red";
                            
                            phoneInput.addEventListener('input', () => {
                                phoneInput.style.borderBottom = ""; 
                            }, { once: true });
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>