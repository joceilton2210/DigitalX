// Cardápio Digital Profissional
document.addEventListener('DOMContentLoaded', () => {
    // Elementos da interface
    const telaCardapio = document.getElementById('tela-cardapio');
    const telaStatusPedido = document.getElementById('tela-status-pedido');
    const cardapioContainer = document.getElementById('cardapio-container');
    const carrinhoItensContainer = document.getElementById('carrinho-itens');
    const carrinhoContador = document.getElementById('carrinho-contador');
    const carrinhoTotal = document.getElementById('carrinho-total');
    const enviarPedidoBtn = document.getElementById('enviar-pedido-btn');

    // Estado da aplicação
    let carrinho = [];
    let monitorInterval = null;
    let pedidoPendenteId = null;
    let pedidoTimestamp = null;

    // Função para formatar tempo decorrido
    function formatarTempoDecorrido(minutos) {
        if (minutos < 60) {
            return `${minutos} min`;
        } else {
            const horas = Math.floor(minutos / 60);
            const mins = minutos % 60;
            return `${horas}h ${mins}min`;
        }
    }

    // Função para atualizar informações do pedido
    function atualizarInformacoesPedido(dados) {
        const pedidoInfo = document.getElementById('pedido-info');
        const infoMesa = document.getElementById('info-mesa');
        const infoTotal = document.getElementById('info-total');
        const tempoDecorrido = document.getElementById('tempo-decorrido');
        const tempoEstimado = document.getElementById('tempo-estimado');
        const listaItens = document.getElementById('lista-itens-pedido');

        if (dados) {
            // Mostrar informações do pedido
            pedidoInfo.style.display = 'block';
            
            // Atualizar dados básicos
            infoMesa.textContent = dados.mesa;
            infoTotal.textContent = `R$ ${parseFloat(dados.valor_total).toFixed(2).replace('.', ',')}`;
            tempoDecorrido.textContent = formatarTempoDecorrido(dados.tempo_decorrido);
            tempoEstimado.textContent = dados.tempo_estimado;

            // Atualizar lista de itens
            if (dados.itens && dados.itens.length > 0) {
                listaItens.innerHTML = dados.itens.map(item => `
                    <div class="item-pedido">
                        <span class="item-nome">${item.produto_nome}</span>
                        <span class="item-quantidade">${item.quantidade}x</span>
                        <span class="item-preco">R$ ${parseFloat(item.preco_unitario * item.quantidade).toFixed(2).replace('.', ',')}</span>
                    </div>
                `).join('');
            }
        }
    }

    // Função para atualizar a tela de status visualmente
    function atualizarTelaStatus(status, dados = null) {
        const statusEnviado = document.getElementById('status-enviado');
        const statusAceito = document.getElementById('status-aceito');
        const statusPronto = document.getElementById('status-pronto');
        const spinner = document.getElementById('status-spinner');
        const botaoNovoPedido = document.getElementById('botao-novo-pedido');
        
        // Resetar todos os status
        [statusEnviado, statusAceito, statusPronto].forEach(el => {
            el.classList.remove('completed', 'active');
            el.querySelector('i').className = 'bi bi-circle text-secondary';
        });

        // Atualizar informações do pedido se disponível
        if (dados) {
            atualizarInformacoesPedido(dados);
        }

        // Aplicar status atual
        if (status === 'Pendente') {
            statusEnviado.classList.add('completed');
            statusEnviado.querySelector('i').className = 'bi bi-check-circle-fill text-success';
            statusAceito.classList.add('active');
            spinner.style.display = 'block';
        } else if (status === 'Em Preparo') {
            statusEnviado.classList.add('completed');
            statusEnviado.querySelector('i').className = 'bi bi-check-circle-fill text-success';
            statusAceito.classList.add('completed');
            statusAceito.querySelector('i').className = 'bi bi-check-circle-fill text-success';
            statusPronto.classList.add('active');
            spinner.style.display = 'block';
        } else if (status === 'Pronto') {
            statusEnviado.classList.add('completed');
            statusEnviado.querySelector('i').className = 'bi bi-check-circle-fill text-success';
            statusAceito.classList.add('completed');
            statusAceito.querySelector('i').className = 'bi bi-check-circle-fill text-success';
            statusPronto.classList.add('completed');
            statusPronto.querySelector('i').className = 'bi bi-check-circle-fill text-success';
            spinner.style.display = 'none';
            botaoNovoPedido.style.display = 'block';
        }
    }
    
    // Função para verificar status do pedido
    function verificarStatusPedido() {
        if (!pedidoPendenteId) return;

        fetch(`api/verificar_status_pedido.php?id=${pedidoPendenteId}`)
            .then(res => res.json())
            .then(data => {
                if (!data || data.erro) {
                    console.error("Erro na resposta:", data?.erro);
                    return;
                }

                // Atualizar tela com dados completos
                atualizarTelaStatus(data.status, data);

                // Verificar se precisa parar o monitoramento
                if (data.status === 'Pronto') {
                    clearInterval(monitorInterval);
                    Swal.fire({
                        icon: 'success',
                        title: 'Pedido Pronto!',
                        text: 'Seu pedido está pronto! Pode chamar o garçom.',
                        confirmButtonText: 'Entendi',
                        allowOutsideClick: false
                    });
                } else if (data.status === 'Em Preparo') {
                    // Mostrar notificação apenas uma vez
                    if (!window.notificacaoEmPreparoMostrada) {
                        window.notificacaoEmPreparoMostrada = true;
                        Swal.fire({
                            icon: 'info',
                            title: 'Em Preparo!',
                            text: 'Nossa cozinha já está preparando seu pedido!',
                            showConfirmButton: false,
                            timer: 3000,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                }

                // Verificar demora (mais de 20 minutos)
                if (data.tempo_decorrido > 20 && data.status !== 'Pronto') {
                    document.getElementById('alerta-demora').style.display = 'block';
                }
            })
            .catch(err => {
                console.error("Erro ao verificar status:", err);
                // Não parar o monitoramento em caso de erro de rede
            });
    }

    // Função para voltar ao cardápio
    window.voltarAoCardapio = () => {
        // Limpar estado
        clearInterval(monitorInterval);
        pedidoPendenteId = null;
        pedidoTimestamp = null;
        carrinho = [];
        window.notificacaoEmPreparoMostrada = false;
        
        // Resetar interface
        atualizarCarrinho();
        document.getElementById('pedido-info').style.display = 'none';
        document.getElementById('alerta-demora').style.display = 'none';
        document.getElementById('botao-novo-pedido').style.display = 'none';
        
        // ✅ TRANSIÇÃO CORRETA: Ocultar status e mostrar cardápio
        telaStatusPedido.classList.remove('tela-visivel');
        telaStatusPedido.style.display = 'none';
        telaCardapio.classList.add('tela-visivel');
        telaCardapio.style.display = 'block';
    };

    // Event listener para enviar pedido
    if (enviarPedidoBtn) {
        enviarPedidoBtn.addEventListener('click', async () => {
            if (carrinho.length === 0) return;
            if (typeof MESA_ATUAL === 'undefined' || MESA_ATUAL === '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Mesa não identificada. Tente escanear o QR Code novamente.'
                });
                return;
            }

            // Mostrar loading no botão
            enviarPedidoBtn.disabled = true;
            enviarPedidoBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

            try {
                const response = await fetch('api/enviar_pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mesa: MESA_ATUAL, itens: carrinho })
                });

                const result = await response.json();

                if (response.ok && result.sucesso) {
                    // Fechar modal
                    bootstrap.Modal.getInstance(document.getElementById('carrinhoModal')).hide();
                    
                    // ✅ TRANSIÇÃO CORRETA: Ocultar cardápio e mostrar status
                    telaCardapio.classList.remove('tela-visivel');
                    telaCardapio.style.display = 'none';
                    telaStatusPedido.classList.add('tela-visivel');
                    telaStatusPedido.style.display = 'block';
                    
                    // Configurar monitoramento
                    pedidoPendenteId = result.pedido_id;
                    pedidoTimestamp = new Date(result.data_hora).getTime();
                    
                    // Primeira verificação imediata
                    verificarStatusPedido();
                    
                    // Iniciar monitoramento a cada 10 segundos
                    monitorInterval = setInterval(verificarStatusPedido, 10000);

                    // Mostrar confirmação
                    Swal.fire({
                        icon: 'success',
                        title: 'Pedido Enviado!',
                        text: 'Seu pedido foi enviado com sucesso.',
                        showConfirmButton: false,
                        timer: 2000,
                        toast: true,
                        position: 'top-end'
                    });

                } else {
                    throw new Error(result.erro || 'Erro desconhecido.');
                }
            } catch (error) {
                // Em caso de erro, permanece na tela do cardápio
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: `Erro ao enviar o pedido: ${error.message}`
                });
            } finally {
                enviarPedidoBtn.disabled = false;
                enviarPedidoBtn.innerHTML = '<i class="bi bi-send"></i> Fazer Pedido';
            }
        });
    }

    // Função para carregar cardápio
    async function carregarCardapio() {
        try {
            const response = await fetch('api/buscar_cardapio.php');
            if (!response.ok) throw new Error('Erro na rede');
            
            const data = await response.json();
            cardapioContainer.innerHTML = '';

            if (data.length === 0) {
                cardapioContainer.innerHTML = `
                    <div class="estado-vazio">
                        <i class="bi bi-menu-button-wide"></i>
                        <h5>Cardápio em atualização</h5>
                        <p>Nosso cardápio está sendo atualizado. Tente novamente em alguns minutos.</p>
                    </div>
                `;
                return;
            }

            data.forEach((categoria, index) => {
                let produtosHtml = categoria.produtos.map(produto => `
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="produto-card">
                            <img src="${produto.imagem_url || 'https://via.placeholder.com/300x220/667eea/ffffff?text=Sem+Imagem'}" 
                                 class="produto-image" 
                                 alt="${produto.nome}"
                                 onerror="this.src='https://via.placeholder.com/300x220/667eea/ffffff?text=Sem+Imagem'">
                            <div class="produto-body">
                                <h5 class="produto-nome">${produto.nome}</h5>
                                <p class="produto-descricao">${produto.descricao || 'Delicioso prato preparado com ingredientes frescos.'}</p>
                                <div class="produto-footer">
                                    <p class="produto-preco">R$ ${parseFloat(produto.preco).toFixed(2).replace('.', ',')}</p>
                                    <button class="btn-adicionar" onclick="adicionarAoCarrinho(${produto.id}, '${produto.nome.replace(/'/g, "\\'")}', ${produto.preco})">
                                        <i class="bi bi-plus-circle"></i> Adicionar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');

                cardapioContainer.innerHTML += `
                    <div class="categoria-section" style="animation-delay: ${index * 0.1}s">
                        <div class="categoria-header">
                            <h2 class="categoria-title">${categoria.nome}</h2>
                            <div class="categoria-divider"></div>
                        </div>
                        <div class="row">
                            ${produtosHtml}
                        </div>
                    </div>
                `;
            });

        } catch (error) {
            console.error('Erro ao carregar cardápio:', error);
            cardapioContainer.innerHTML = `
                <div class="estado-vazio">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <h5>Erro ao carregar cardápio</h5>
                    <p>Não foi possível carregar o cardápio. Verifique sua conexão e tente novamente.</p>
                    <button onclick="location.reload()" class="btn btn-fazer-pedido">
                        <i class="bi bi-arrow-clockwise"></i> Tentar Novamente
                    </button>
                </div>
            `;
        }
    }

    // Função para adicionar ao carrinho
    window.adicionarAoCarrinho = (id, nome, preco) => {
        const itemExistente = carrinho.find(item => item.id === id);
        
        if (itemExistente) {
            itemExistente.quantidade++;
        } else {
            carrinho.push({ id, nome, preco, quantidade: 1 });
        }
        
        atualizarCarrinho();
        
        // Feedback visual
        Swal.fire({
            icon: 'success',
            title: 'Adicionado!',
            text: `${nome} foi adicionado ao carrinho`,
            showConfirmButton: false,
            timer: 1500,
            toast: true,
            position: 'top-end'
        });
    };

    // Função para atualizar carrinho
    const atualizarCarrinho = () => {
        carrinhoItensContainer.innerHTML = '';
        
        if (carrinho.length === 0) {
            carrinhoItensContainer.innerHTML = `
                <div class="estado-vazio">
                    <i class="bi bi-cart-x"></i>
                    <h5>Seu carrinho está vazio</h5>
                    <p>Adicione alguns itens deliciosos do nosso cardápio!</p>
                </div>
            `;
            enviarPedidoBtn.disabled = true;
        } else {
            enviarPedidoBtn.disabled = false;
            
            carrinho.forEach((item, index) => {
                carrinhoItensContainer.innerHTML += `
                    <div class="carrinho-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <div class="carrinho-item-nome">${item.nome}</div>
                                <div class="carrinho-item-preco">R$ ${parseFloat(item.preco).toFixed(2).replace('.', ',')}</div>
                            </div>
                            <div class="quantidade-controls">
                                <button class="btn-quantidade" onclick="mudarQuantidade(${index}, -1)">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span class="quantidade-display">${item.quantidade}</span>
                                <button class="btn-quantidade" onclick="mudarQuantidade(${index}, 1)">
                                    <i class="bi bi-plus"></i>
                                </button>
                                <button class="btn-remover ms-3" onclick="removerItem(${index})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        // Atualizar total e contador
        const total = carrinho.reduce((acc, item) => acc + (item.preco * item.quantidade), 0);
        carrinhoTotal.textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
        
        const totalItens = carrinho.reduce((acc, item) => acc + item.quantidade, 0);
        carrinhoContador.textContent = totalItens;
        carrinhoContador.style.display = totalItens > 0 ? 'flex' : 'none';
    };

    // Função para mudar quantidade
    window.mudarQuantidade = (index, delta) => {
        if (carrinho[index].quantidade + delta > 0) {
            carrinho[index].quantidade += delta;
        } else {
            carrinho.splice(index, 1);
        }
        atualizarCarrinho();
    };

    // Função para remover item
    window.removerItem = (index) => {
        const item = carrinho[index];
        carrinho.splice(index, 1);
        atualizarCarrinho();
        
        Swal.fire({
            icon: 'info',
            title: 'Removido!',
            text: `${item.nome} foi removido do carrinho`,
            showConfirmButton: false,
            timer: 1500,
            toast: true,
            position: 'top-end'
        });
    };

    // Inicializar aplicação
    if (cardapioContainer) {
        carregarCardapio();
    }
    
    atualizarCarrinho();
});