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
                    <input type="tel" id="reg-phone" name="telefone" placeholder=" " required />
                    <label for="reg-phone">Telefone</label>
                </div>
                <div class="input-group">
                    <input type="password" name="senha" placeholder=" " required>
                    <label>Senha</label>
                </div>

                <div class="input-group">
                    <input type="text" name="codigo_indicacao" id="input-cupom" placeholder=" " style="color: var(--gold); font-weight: bold;">
                    <label for="input-cupom">Código de convite (opcional)</label>
                </div>

                <button type="submit" class="btn-submit">Cadastrar</button>
                
            </form>
        </div>

        <div class="form-container sign-in-container">
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
                </div>
                <a href="#" class="form-link forgot-pass">Esqueceu sua senha?</a>
                <button type="submit" class="btn-submit">Entrar</button>

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
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('container');
            
            // Botões do Desktop (Overlay)
            const signUpButton = document.getElementById('signUp');
            const signInButton = document.getElementById('signIn');

            // Links de texto do Mobile
            const signUpLinkMobile = document.getElementById('signUpMobile');
            const signInLinkMobile = document.getElementById('signInMobile');

            const showSignUp = () => {
                container.classList.add('active');
            };

            const showSignIn = () => {
                container.classList.remove('active');
            };

            // Listeners para Desktop
            signUpButton.addEventListener('click', showSignUp);
            signInButton.addEventListener('click', showSignIn);

            // Listeners para Mobile
            signUpLinkMobile.addEventListener('click', (e) => {
                e.preventDefault();
                showSignUp();
            });
            signInLinkMobile.addEventListener('click', (e) => {
                e.preventDefault();
                showSignIn();
            });
        });

    function fazerLogin(event) {
        event.preventDefault(); // <--- ISSO IMPEDE A TELA PRETA (submit padrão)

        const form = document.getElementById('formLogin');
        const formData = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        
        // Efeito visual no botão
        const textoOriginal = btn.innerHTML;
        btn.innerHTML = 'Entrando...';
        btn.disabled = true;

        fetch('actions/auth_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // O PHP mandou o link certo, o JS obedece
                window.location.href = data.redirect;
            } else {
                alert(data.message); // Mostra erro (senha errada, etc)
                btn.innerHTML = textoOriginal;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão.');
            btn.innerHTML = textoOriginal;
            btn.disabled = false;
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref'); // Pega ?ref=CODIGO
        if (refCode) {
            const input = document.getElementById('input-cupom');
            if (input) {
                input.value = refCode;
                input.readOnly = true; // Trava para não editar
            }
        }
    });

    </script>
</body>
</html>