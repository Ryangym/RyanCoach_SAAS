<footer class="site-footer-v2">
        <div class="footer-glow-bar"></div>

        <div class="footer-container-v2">
            
            <div class="footer-col" id="footer-brand">
                <h2 class="logo">Ryan Coach</h2>
                <p>Onde foco, disciplina e ciência se encontram para transformar seu físico.</p>
                
                <div class="social-links-v2">
                    <a href="https://api.whatsapp.com/send?phone=5535999928473&text=Ol%C3%A1,%20gostaria%20de%20conversar%20sobre%20seu%20planejamento%20de%20treino." target="_blank" title="WhatsApp" style="--social-color: #25D366;">
                        <img src="assets/img/icones/whatsapp-fill-svgrepo-com.svg" alt="WhatsApp">
                    </a>
                    <a href="https://www.instagram.com/ryan.gym_/" target="_blank" title="Instagram" style="--social-color: #E4405F;">
                        <img src="assets/img/icones/instagram-fill-svgrepo-com.svg" alt="Instagram">
                    </a>
                    <a href="https://www.tiktok.com/@ryan.gym_" target="_blank" title="TikTok" style="--social-color: #8f16aa;">
                        <img src="assets/img/icones/tiktok-fill-svgrepo-com.svg" alt="TikTok">
                    </a>
                    <a href="https://t.me/ryanborges" target="_blank" title="Telegram" style="--social-color: #229ED9;">
                        <img src="assets/img/icones/telegram-fill-svgrepo-com.svg" alt="Telegram">
                    </a>
                    <a href="https://www.youtube.com/channel/UCD6fzhXmxlFCUb91uRUCBVw" target="_blank" title="YouTube" style="--social-color: #FF0000;">
                        <img src="assets/img/icones/youtube-fill-svgrepo-com.svg" alt="YouTube">
                    </a>
                </div>
            </div>

        <div class="footer-col" id="footer-links">
            <h4>Navegação</h4>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="planos.php">Nossos Planos</a></li>
                <li><a href="usuario.php">Área do Aluno</a></li>
                <li><a href="sobre.php">Sobre Ryan Borges</a></li>
                <li><a href="ferramentas.php">Ferramentas</a></li>
            </ul>
        </div>

        <div class="footer-col" id="footer-form">
            <h4>Mande uma Mensagem</h4>
            <form id="form-contato-footer">
                <div class="input-group-v2">
                    <input type="text" id="footer-name" name="name" placeholder="Nome Completo" required>
                </div>
                <div class="input-group-v2">
                    <input type="email" id="footer-email" name="email" placeholder="Seu Email" required>
                </div>
                 <div class="input-group-v2">
                    <input type="tel" id="footer-phone" name="phone" placeholder="Seu Telefone" required>
                </div>
                <div class="input-group-v2">
                    <textarea id="footer-message" name="message" rows="4" placeholder="Sua Mensagem..." required></textarea>
                </div>
                <button type="submit" class="footer-submit-btn-v2" id="btn-enviar-msg">Enviar</button>
            </form>
        </div>

    </div> <div class="footer-bottom-v2">
        <p>&copy; 2025 Ryan Coach. Todos os direitos reservados. Desenvolvido por Ryan Borges</p>
    </div>
</footer>

<script>
document.getElementById('form-contato-footer').addEventListener('submit', async function(e) {
    e.preventDefault(); // Não recarrega a página
    
    const btn = document.getElementById('btn-enviar-msg');
    const originalText = btn.innerText;
    btn.innerText = 'Enviando...';
    btn.disabled = true;

    const formData = new FormData(this);

    try {
        const response = await fetch('actions/mensagem_save.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if(data.status === 'success') {
            alert('Recebemos sua mensagem! Entraremos em contato em breve.');
            this.reset(); // Limpa o formulário
        } else {
            alert('Erro: ' + data.msg);
        }
    } catch (error) {
        console.error(error);
        alert('Erro de conexão. Tente novamente.');
    } finally {
        btn.innerText = originalText;
        btn.disabled = false;
    }
});
</script>