document.addEventListener('DOMContentLoaded', () => {
    const cardapioContainer = document.getElementById('cardapio-container');
    const resumoItensContainer = document.getElementById('resumo-pedido-itens');
    const resumoSubtotalEl = document.getElementById('resumo-subtotal');
    const fecharContaBtn = document.getElementById('fechar-conta-btn');
    const pedidoIdInput = document.getElementById('pedido-id-atual');

    // Função para renderizar o resumo do pedido na tela
    function atualizarResumoPedido(pedido) {
        if (!pedido || !pedido.itens || pedido.itens.length === 0) {
            resumoItensContainer.innerHTML = '<p class="text-muted">Nenhum item adicionado ainda.</p>';
            resumoSubtotalEl.textContent = 'R$ 0,00';
            fecharContaBtn.disabled = true;
            pedidoIdInput.value = '';
            return;
        }

        let itensHtml = '<ul class="list-group list-group-flush">';
        pedido.itens.forEach(item => {
            const subtotalFormatado = parseFloat(item.subtotal).toFixed(2).replace('.', ',');
            itensHtml += `<li class="list-group-item d-flex justify-content-between"><span>${item.quantidade}x ${item.nome}</span> <span>R$ ${subtotalFormatado}</span></li>`;
        });
        itensHtml += '</ul>';
        
        resumoItensContainer.innerHTML = itensHtml;
        resumoSubtotalEl.textContent = `R$ ${parseFloat(pedido.valor_total).toFixed(2).replace('.', ',')}`;
        pedidoIdInput.value = pedido.id;
        fecharContaBtn.disabled = false;
    }

    // Função que carrega o pedido inicial da mesa ao abrir a página
    async function carregarPedidoInicial() {
        try {
            const response = await fetch(`../api/ver_pedido_mesa.php?mesa_identificador=${MESA_ATUAL}`);
            const pedido = await response.json();
            if (response.ok) {
                atualizarResumoPedido(pedido);
            } else {
                throw new Error(pedido.erro);
            }
        } catch (error) {
            Swal.fire('Oops...', `Não foi possível carregar o pedido da mesa: ${error.message}`, 'error');
        }
    }

    // Adiciona um item ao pedido
    window.adicionarItem = async (produtoId) => {
        const formData = new FormData();
        formData.append('mesa_identificador', MESA_ATUAL);
        formData.append('produto_id', produtoId);

        try {
            const response = await fetch('../api/adicionar_item_garcom.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (response.ok) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Item adicionado!', showConfirmButton: false, timer: 1500 });
                // Atualiza o resumo com os dados retornados pela API
                atualizarResumoPedido(result.pedido_atualizado);
            } else {
                throw new Error(result.erro);
            }
        } catch (error) {
            Swal.fire('Oops...', `Erro ao adicionar item: ${error.message}`, 'error');
        }
    };

    // Evento para fechar a conta
    fecharContaBtn.addEventListener('click', () => {
        const pedidoId = pedidoIdInput.value;
        if (!pedidoId) return;

        Swal.fire({
            title: 'Fechar a conta?',
            text: "A taxa de serviço de 10% será adicionada e o pedido será enviado para o caixa. Esta ação não pode ser desfeita.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, fechar conta!',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('pedido_id', pedidoId);
                try {
                    const response = await fetch('../api/fechar_conta.php', { method: 'POST', body: formData });
                    const res = await response.json();
                    if(response.ok) {
                        Swal.fire('Conta Fechada!', 'O pedido foi enviado para o caixa.', 'success')
                            .then(() => window.location.href = 'index.php'); // Redireciona para o painel
                    } else {
                        throw new Error(res.erro);
                    }
                } catch(error) {
                    Swal.fire('Oops...', `Erro ao fechar a conta: ${error.message}`, 'error');
                }
            }
        });
    });

    // Carrega o cardápio (função do código anterior, adicione-a aqui)
    async function carregarCardapio() { const response = await fetch('../api/buscar_cardapio.php'); const data = await response.json(); cardapioContainer.innerHTML = ''; data.forEach(categoria => { let produtosHtml = categoria.produtos.map(produto => `<div class="col-md-4 mb-3"><button class="btn btn-outline-primary w-100 h-100 p-3" onclick="adicionarItem(${produto.id})"><h5>${produto.nome}</h5><p class="mb-0">R$ ${parseFloat(produto.preco).toFixed(2).replace('.', ',')}</p></button></div>`).join(''); cardapioContainer.innerHTML += `<div class="mb-4"><h3>${categoria.nome}</h3><hr><div class="row">${produtosHtml}</div></div>`; }); }

    // Funções iniciais
    carregarCardapio();
    carregarPedidoInicial();
});