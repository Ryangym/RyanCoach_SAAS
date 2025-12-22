<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php include 'includes/head_main.php'; ?>
    <title>Acesso Administrativo - Ryan Coach</title>
    
    <style>
        /* =========================================
           RESET E BASE
           ========================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: url('assets/img/background-gym.png') no-repeat center center fixed;
            background-size: cover;
            height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Overlay escuro para dar leitura */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75); /* Escurece o fundo */
            z-index: 1;
        }

        /* =========================================
           CARD DE LOGIN (Vidro Fosco)
           ========================================= */
        .admin-card {
            position: relative;
            z-index: 2;
            background: rgba(20, 20, 20, 0.65);
            backdrop-filter: blur(15px); /* Efeito de vidro */
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 66, 66, 0.5); /* Borda vermelha sutil no topo */
            border-bottom: 1px solid rgba(255, 66, 66, 0.5);
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.8), 
                        0 0 15px rgba(255, 66, 66, 0.1); /* Glow vermelho fraco */
            animation: fadeInUp 0.8s ease forwards;
            margin: 20px; /* Margem para não colar na borda no mobile */
        }

        /* Animação de entrada */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* =========================================
           ELEMENTOS DO FORM
           ========================================= */
        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-logo {
            font-family: 'Ms Madi', cursive;
            font-size: 2.5rem;
            color: #fff;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
            margin-bottom: 5px;
            display: block;
        }

        .badge-admin {
            background: rgba(255, 66, 66, 0.2);
            color: #ff4242;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 1px solid rgba(255, 66, 66, 0.3);
            box-shadow: 0 0 10px rgba(255, 66, 66, 0.2);
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            transition: 0.3s;
        }

        .form-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            padding: 12px 15px 12px 45px; /* Espaço para o ícone */
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: 0.3s all;
        }

        .form-input:focus {
            border-color: #ff4242;
            background: rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 10px rgba(255, 66, 66, 0.2);
        }

        .form-input:focus + i {
            color: #ff4242;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #d40606, #ff4242);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(255, 66, 66, 0.4);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 66, 66, 0.6);
            filter: brightness(1.1);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #888;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #fff;
        }

        /* =========================================
           RESPONSIVIDADE
           ========================================= */
        @media (max-width: 480px) {
            .admin-card {
                padding: 30px 20px;
                max-width: 90%;
            }
            .admin-logo {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <div class="admin-card">
        <div class="admin-header">
            <span class="admin-logo">Ryan Coach</span>
            <span class="badge-admin"><i class="fa-solid fa-lock"></i> Área Restrita</span>
        </div>

        <form action="actions/auth_login.php" method="POST">
            <input type="hidden" name="tipo_login" value="admin">
            
            <div class="input-group">
                <input type="email" name="email" class="form-input" placeholder="E-mail de Acesso" required autocomplete="off">
                <i class="fa-regular fa-envelope"></i>
            </div>

            <div class="input-group">
                <input type="password" name="senha" class="form-input" placeholder="Senha" required>
                <i class="fa-solid fa-key"></i>
            </div>

            <button type="submit" class="btn-submit">
                Acessar Painel <i class="fa-solid fa-arrow-right" style="margin-left: 5px;"></i>
            </button>

        </form>

        <a href="login.php" class="back-link">
            <i class="fa-solid fa-chevron-left"></i> Voltar para Login Aluno
        </a>
    </div>

</body>
</html>