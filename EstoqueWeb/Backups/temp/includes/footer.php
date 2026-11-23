
    </section>

    <footer class="text-center text-muted small py-3">
        EstoqueWeb &copy; <?= date('Y') ?> -
        Controle de Estoque e Almoxarifado
    </footer>

</main>

<!-- MODAL PADRÃO DE CONFIRMAÇÃO -->

<div class="modal fade" id="modalConfirmacao" tabindex="-1"
     aria-labelledby="modalConfirmacaoTitulo" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="modalConfirmacaoTitulo">
                    <i class="bi bi-question-circle text-primary me-2"></i>
                    Confirmação
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"></button>

            </div>

            <div class="modal-body" id="modalConfirmacaoMensagem">
                Deseja realmente executar esta operação?
            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="button"
                        class="btn btn-principal"
                        id="btnConfirmarAcao">
                    <i class="bi bi-check-circle me-1"></i>
                    Confirmar
                </button>

            </div>

        </div>

    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function() {

    const elementoModal = document.getElementById('modalConfirmacao');

    const modal = new bootstrap.Modal(elementoModal);

    const mensagem = document.getElementById('modalConfirmacaoMensagem');

    const botaoConfirmar = document.getElementById('btnConfirmarAcao');

    let formularioPendente = null;

    // Intercepta formulários que solicitam confirmação.
    document.addEventListener('submit', function(evento) {

        const formulario = evento.target;

        if (!formulario.matches('form[data-confirm]')) {
            return;
        }

        // Permite o envio após a confirmação.
        if (formulario.dataset.confirmado === '1') {

            delete formulario.dataset.confirmado;

            return;
        }

        evento.preventDefault();

        formularioPendente = formulario;

        mensagem.textContent =
            formulario.dataset.confirm ||
            'Deseja realmente executar esta operação?';

        modal.show();

    });

    // Confirma a operação.
    botaoConfirmar.addEventListener('click', function() {

        if (!formularioPendente) {
            return;
        }

        const formulario = formularioPendente;

        formularioPendente = null;

        formulario.dataset.confirmado = '1';

        modal.hide();

        formulario.requestSubmit();

    });

    // Limpa a referência caso o usuário cancele.
    elementoModal.addEventListener('hidden.bs.modal', function() {

        formularioPendente = null;

    });

});

</script>

<script>
function atualizarDataHora() {
    const agora = new Date();
    const opcoes = { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' };
    const data = agora.toLocaleDateString('pt-BR', opcoes);
    const hora = agora.toLocaleTimeString('pt-BR');
    const el = document.getElementById('data-hora-topo');
    if (el) {
        // Deixa a primeira letra maiúscula: sábado -> Sábado
        el.textContent = data.charAt(0).toUpperCase() + data.slice(1) + ' - ' + hora;
    }
}
atualizarDataHora();
setInterval(atualizarDataHora, 1000);
</script>
</body>
</html>

