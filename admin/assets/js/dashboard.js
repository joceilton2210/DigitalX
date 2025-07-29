// Dashboard Moderno - Sistema de Pedidos por Setores
document.addEventListener('DOMContentLoaded', () => {
    const colunas = {
        // Cozinha
        'cozinha-pendente': document.getElementById('coluna-cozinha-pendente'),
        'cozinha-preparo': document.getElementById('coluna-cozinha-preparo'),
        'cozinha-pronto': document.getElementById('coluna-cozinha-pronto'),
        'cozinha-pago': document.getElementById('coluna-cozinha-pago'),
        // Bebidas
        'bebidas-pendente': document.getElementById('coluna-bebidas-pendente'),
        'bebidas-preparo': document.getElementById('coluna-bebidas-preparo'),
        'bebidas-pronto': document.getElementById('coluna-bebidas-pronto'),
        'bebidas-pago': document.getElementById('coluna-bebidas-pago')
    };
    
    const contadores = {
        // Cozinha
        'cozinha-pendente': document.getElementById('count-cozinha-pendente'),
        'cozinha-preparo': document.getElementById('count-cozinha-preparo'),
        'cozinha-pronto': document.getElementById('count-cozinha-pronto'),
        'cozinha-pago': document.getElementById('count-cozinha-pago'),
        // Bebidas
        'bebidas-pendente': document.getElementById('count-bebidas-pendente'),
        'bebidas-preparo': document.getElementById('count-bebidas-preparo'),
        'bebidas-pronto': document.getElementById('count-bebidas-pronto'),
        'bebidas-pago': document.getElementById('count-bebidas-pago')
    };
    
    const estatisticas = {
        Pendente: document.getElementById('stat-pendente'),
        'Em Preparo': document.getElementById('stat-preparo'),
        Pronto: document.getElementById('stat-pronto')
    };

    const faturamentoEl = document.getElementById('stat-faturamento');
    const audio = document.getElementById('notification-sound');
    let isAlertActive = false;
    let ultimaAtualizacao = null;

    // ✅ NOVA: Função para identificar se é bebida
    function ehBebida(itens) {
        const palavrasBebida = [
            'bebida', 'drink', 'cerveja', 'refrigerante', 'suco', 'água', 'coca', 'pepsi', 
            'guaraná', 'fanta', 'sprite', 'heineken', 'skol', 'brahma', 'stella', 'corona',
            'vinho', 'caipirinha', 'vodka', 'whisky', 'rum', 'gin', 'tequila', 'licor',
            'café', 'chá', 'cappuccino', 'expresso', 'latte', 'smoothie', 'milkshake',
            'energético', 'isotônico', 'mineral', 'gasosa', 'natural'
        ];
        
        return itens.some(item => {
            const nomeItem = item.produto_nome.toLowerCase();
            return palavrasBebida.some(palavra => nomeItem.includes(palavra));
        });
    }

    // ✅ NOVA: Função para determinar setor do pedido
    function determinarSetor(pedido) {
        return ehBebida(pedido.itens) ? 'bebidas' : 'cozinha';
    }

    // Função para formatar tempo decorrido
    function formatarTempo(segundos) {
        if (segundos < 0) segundos = 0;
        const horas = Math.floor(segundos / 3600);
        const minutos = Math.floor((segundos % 3600) / 60);
        const segs = Math.floor(segundos % 60);
        
        if (horas > 0) {
            return `${horas}h ${minutos}m`;
        } else if (minutos > 0) {
            return `${minutos}m`;
        } else {
            return `${segs}s`;
        }
    }
    
    // Atualizar timers em tempo real
    setInterval(() => {
        document.querySelectorAll('.timer-badge[data-timestamp]').forEach(timerEl => {
            const timestamp = new Date(timerEl.dataset.timestamp).getTime();
            const agora = new Date().getTime();
            const diferencaSegundos = (agora - timestamp) / 1000;
            
            timerEl.textContent = formatarTempo(diferencaSegundos);
            
            const card = timerEl.closest('.card-pedido');
            if (diferencaSegundos > 600) { // 10 minutos
                card.classList.add('pedido-urgente');
                timerEl.style.background = 'var(--danger-color)';
            } else if (diferencaSegundos > 300) { // 5 minutos
                timerEl.style.background = 'var(--warning-color)';
            }
        });
    }, 1000);

    // Função principal para buscar pedidos
    async function buscarPedidos() {
        try {
            const response = await fetch('../api/buscar_pedidos.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Verificar se há erro na resposta
            if (data.erro) {
                throw new Error(data.erro);
            }
            
            const pedidos = data.pedidos || data;
            const faturamento = data.faturamento_hoje || 0;
            
            // Atualizar faturamento
            if (faturamentoEl) {
                faturamentoEl.textContent = `R$ ${parseFloat(faturamento).toFixed(2).replace('.', ',')}`;
            }
            
            // Verificar pedidos pendentes para alerta sonoro
            const hasPendingOrders = pedidos.some(p => p.status === 'Pendente');
            
            if (hasPendingOrders && !isAlertActive) {
                audio.play().catch(e => console.log("Áudio não pôde ser reproduzido:", e));
                isAlertActive = true;
            } else if (!hasPendingOrders && isAlertActive) {
                audio.pause();
                audio.currentTime = 0;
                isAlertActive = false;
            }

            // Limpar colunas
            Object.values(colunas).forEach(col => col.innerHTML = '');
            
            // Contar pedidos por setor e status
            const contagem = {
                'cozinha-pendente': 0,
                'cozinha-preparo': 0,
                'cozinha-pronto': 0,
                'cozinha-pago': 0,
                'bebidas-pendente': 0,
                'bebidas-preparo': 0,
                'bebidas-pronto': 0,
                'bebidas-pago': 0
            };
            
            // Contagem geral para estatísticas
            const contagemGeral = {
                'Pendente': 0,
                'Em Preparo': 0,
                'Pronto': 0
            };
            
            if (pedidos.length === 0) {
                // Mostrar estado vazio
                Object.keys(colunas).forEach(chave => {
                    if (colunas[chave]) {
                        colunas[chave].innerHTML = criarEstadoVazio(chave);
                    }
                });
            } else {
                // Processar pedidos por setor
                pedidos.forEach(pedido => {
                    const setor = determinarSetor(pedido);
                    const status = pedido.status === 'Em Preparo' ? 'preparo' : pedido.status.toLowerCase();
                    const chaveColuna = `${setor}-${status}`;
                    
                    // Contar para o setor específico
                    if (contagem.hasOwnProperty(chaveColuna)) {
                        contagem[chaveColuna]++;
                    }
                    
                    // Contar para estatísticas gerais (exceto Pago)
                    if (pedido.status !== 'Pago') {
                        contagemGeral[pedido.status]++;
                    }
                    
                    // Adicionar à coluna correta
                    const colunaDestino = colunas[chaveColuna];
                    if (colunaDestino) {
                        colunaDestino.innerHTML += criarCardPedido(pedido, setor);
                    }
                });
            }
            
            // Atualizar contadores por setor
            Object.keys(contagem).forEach(chave => {
                if (contadores[chave]) {
                    contadores[chave].textContent = contagem[chave];
                }
            });
            
            // Atualizar estatísticas gerais
            Object.keys(contagemGeral).forEach(status => {
                if (estatisticas[status]) {
                    estatisticas[status].textContent = contagemGeral[status];
                }
            });
            
            ultimaAtualizacao = new Date();
            
        } catch (error) {
            console.error('Erro ao buscar pedidos:', error);
            
            // Mostrar erro nas colunas
            Object.values(colunas).forEach(col => {
                col.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <p>Erro ao carregar pedidos</p>
                    </div>
                `;
            });
            
            // Parar alerta sonoro em caso de erro
            if (isAlertActive) {
                audio.pause();
                audio.currentTime = 0;
                isAlertActive = false;
            }
        }
    }
    
    // Criar estado vazio para colunas
    function criarEstadoVazio(status) {
        const icones = {
            'Pendente': 'clock-history',
            'Em Preparo': 'fire',
            'Pronto': 'check-circle',
            'Pago': 'credit-card' // ✅ NOVO
        };
        
        const mensagens = {
            'Pendente': 'Nenhum pedido pendente',
            'Em Preparo': 'Nenhum pedido em preparo',
            'Pronto': 'Nenhum pedido pronto',
            'Pago': 'Nenhum pedido aguardando finalização' // ✅ NOVO
        };
        
        return `
            <div class="empty-state">
                <i class="bi bi-${icones[status]}"></i>
                <p>${mensagens[status]}</p>
            </div>
        `;
    }
    
    // Criar card de pedido com design moderno
    function criarCardPedido(pedido) {
        const itensHtml = pedido.itens.map(item => 
            `<li>
                <span>${item.produto_nome}</span>
                <span class="item-quantidade">${item.quantidade}</span>
            </li>`
        ).join('');
        
        const horaFormatada = new Date(pedido.data_hora).toLocaleTimeString('pt-BR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        let origemIcon = '', origemText = '';
        if (pedido.origem === 'Garçom') {
            origemIcon = '<i class="bi bi-person-check-fill"></i>';
            origemText = 'Garçom';
        } else {
            origemIcon = '<i class="bi bi-phone-fill"></i>';
            origemText = 'Cliente';
        }
        
        let timerHtml = '';
        if (pedido.status === 'Pendente' || pedido.status === 'Em Preparo') {
            timerHtml = `<div class="timer-badge" data-timestamp="${pedido.data_hora}">Calculando...</div>`;
        }
        
        let botaoAcao = '';
        if (pedido.status === 'Pendente') {
            botaoAcao = `
                <div class="card-footer-custom">
                    <button class="btn-acao btn-aceitar" onclick="atualizarStatus(${pedido.id}, 'Em Preparo')">
                        <i class="bi bi-play-circle"></i>
                        Iniciar Preparo
                    </button>
                </div>
            `;
        } else if (pedido.status === 'Em Preparo') {
            botaoAcao = `
                <div class="card-footer-custom">
                    <button class="btn-acao btn-finalizar" onclick="atualizarStatus(${pedido.id}, 'Pronto')">
                        <i class="bi bi-check-circle"></i>
                        Marcar como Pronto
                    </button>
                </div>
            `;
        } else if (pedido.status === 'Pronto') {
            // ✅ MODIFICADO: Botão para marcar como pago
            botaoAcao = `
                <div class="card-footer-custom">
                    <button class="btn-acao btn-pagar" onclick="marcarComoPago(${pedido.id})">
                        <i class="bi bi-credit-card"></i>
                        Cliente Pagou
                    </button>
                </div>
            `;
        } else if (pedido.status === 'Pago') {
            // ✅ NOVO: Botões para finalizar com método de pagamento
            botaoAcao = `
                <div class="card-footer-custom">
                    <div class="metodos-pagamento">
                        <p class="mb-2"><strong>Finalizar com:</strong></p>
                        <div class="btn-group-vertical w-100" role="group">
                            <button class="btn-acao btn-pix" onclick="finalizarPagamento(${pedido.id}, 'PIX')">
                                <i class="bi bi-qr-code"></i> PIX
                            </button>
                            <button class="btn-acao btn-credito" onclick="finalizarPagamento(${pedido.id}, 'Crédito')">
                                <i class="bi bi-credit-card"></i> Crédito
                            </button>
                            <button class="btn-acao btn-debito" onclick="finalizarPagamento(${pedido.id}, 'Débito')">
                                <i class="bi bi-credit-card-2-front"></i> Débito
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        const statusClass = pedido.status.toLowerCase().replace(' ', '_');
        
        // ✅ CORREÇÃO: Tratar casos onde mesa_numero pode ser undefined ou null
        const mesaNumero = pedido.mesa_numero || pedido.numero_mesa || 'N/A';
        const mesaDisplay = mesaNumero === 'N/A' ? 
            `<span style="color: #e74c3c;">Mesa ${mesaNumero}</span>` : 
            `Mesa ${mesaNumero}`;
        
        return `
            <div class="card-pedido ${statusClass}" data-pedido-id="${pedido.id}">
                <div class="card-header-custom">
                    <div class="mesa-info">
                        <div class="mesa-numero">
                            <i class="bi bi-table"></i>
                            ${mesaDisplay}
                        </div>
                        <div class="pedido-hora">${horaFormatada}</div>
                    </div>
                </div>
                
                <div class="card-body-custom">
                    <div class="pedido-info">
                        <div class="pedido-id">#${pedido.id}</div>
                        <div class="origem-badge">
                            ${origemIcon}
                            ${origemText}
                        </div>
                    </div>
                    
                    ${timerHtml}
                    
                    <ul class="itens-lista">
                        ${itensHtml}
                    </ul>
                    
                    <div class="valor-total">
                        Total: R$ ${parseFloat(pedido.valor_total).toFixed(2).replace('.', ',')}
                    </div>
                </div>
                
                ${botaoAcao}
            </div>
        `;
    }
    
    // Função para atualizar status do pedido
    window.atualizarStatus = async function(pedidoId, novoStatus) {
        try {
            const response = await fetch('../api/atualizar_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pedido_id: pedidoId,
                    status: novoStatus
                })
            });
            
            const result = await response.json();
            
            if (result.sucesso) {
                // Mostrar notificação de sucesso
                Swal.fire({
                    icon: 'success',
                    title: 'Status atualizado!',
                    text: `Pedido #${pedidoId} marcado como "${novoStatus}"`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                
                // Atualizar a lista imediatamente
                buscarPedidos();
            } else {
                throw new Error(result.erro || 'Erro ao atualizar status');
            }
        } catch (error) {
            console.error('Erro ao atualizar status:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: error.message,
                confirmButtonColor: 'var(--danger-color)'
            });
        }
    };
    
    // Inicializar dashboard
    buscarPedidos();
    
    // Atualizar a cada 5 segundos
    setInterval(buscarPedidos, 5000);
    
    // Mostrar última atualização
    setInterval(() => {
        if (ultimaAtualizacao) {
            const agora = new Date();
            const diferenca = Math.floor((agora - ultimaAtualizacao) / 1000);
            
            if (diferenca < 60) {
                console.log(`Última atualização: ${diferenca}s atrás`);
            }
        }
    }, 10000);
});

// Função para formatar moeda
function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

// Função para animar contadores
function animarContador(elemento, valorFinal) {
    const valorInicial = parseInt(elemento.textContent) || 0;
    const diferenca = valorFinal - valorInicial;
    const duracao = 500; // 500ms
    const incremento = diferenca / (duracao / 16); // 60fps
    
    let valorAtual = valorInicial;
    
    const timer = setInterval(() => {
        valorAtual += incremento;
        
        if ((incremento > 0 && valorAtual >= valorFinal) || 
            (incremento < 0 && valorAtual <= valorFinal)) {
            valorAtual = valorFinal;
            clearInterval(timer);
        }
        
        elemento.textContent = Math.round(valorAtual);
    }, 16);
}

// Adicionar efeitos visuais
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-acao')) {
        e.target.style.transform = 'scale(0.95)';
        setTimeout(() => {
            e.target.style.transform = '';
        }, 150);
    }
});

// Notificações do sistema
function mostrarNotificacao(titulo, mensagem, tipo = 'info') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    Toast.fire({
        icon: tipo,
        title: titulo,
        text: mensagem
    });
}


// ✅ NOVA: Função para marcar como pago
window.marcarComoPago = async function(pedidoId) {
    try {
        const response = await fetch('../api/atualizar_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pedido_id: pedidoId,
                status: 'Pago'
            })
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            Swal.fire({
                icon: 'success',
                title: 'Cliente pagou!',
                text: `Pedido #${pedidoId} marcado como pago. Escolha o método de pagamento para finalizar.`,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            
            buscarPedidos();
        } else {
            throw new Error(result.erro || 'Erro ao marcar como pago');
        }
    } catch (error) {
        console.error('Erro ao marcar como pago:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message,
            confirmButtonColor: 'var(--danger-color)'
        });
    }
};

// ✅ NOVA: Função para finalizar pagamento com método
window.finalizarPagamento = async function(pedidoId, metodoPagamento) {
    try {
        const response = await fetch('../api/finalizar_pagamento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pedido_id: pedidoId,
                metodo_pagamento: metodoPagamento
            })
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            Swal.fire({
                icon: 'success',
                title: 'Pagamento finalizado!',
                text: `Pedido #${pedidoId} finalizado via ${metodoPagamento}`,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            
            buscarPedidos();
        } else {
            throw new Error(result.erro || 'Erro ao finalizar pagamento');
        }
    } catch (error) {
        console.error('Erro ao finalizar pagamento:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message,
            confirmButtonColor: 'var(--danger-color)'
        });
    }
};