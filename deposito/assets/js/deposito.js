// JavaScript do Depósito - Sistema de Pedidos
document.addEventListener('DOMContentLoaded', () => {
    const colunas = {
        'bebidas-pendente': document.getElementById('coluna-bebidas-pendente'),
        'bebidas-preparo': document.getElementById('coluna-bebidas-preparo'),
        'bebidas-pronto': document.getElementById('coluna-bebidas-pronto'),
        'bebidas-finalizado': document.getElementById('coluna-bebidas-finalizado')
    };

    const contadores = {
        'bebidas-pendente': document.getElementById('count-bebidas-pendente'),
        'bebidas-preparo': document.getElementById('count-bebidas-preparo'),
        'bebidas-pronto': document.getElementById('count-bebidas-pronto'),
        'bebidas-finalizado': document.getElementById('count-bebidas-finalizado')
    };

    // Função para identificar se é bebida
    function ehBebida(nomeItem) {
        const bebidas = [
            'água', 'refrigerante', 'suco', 'cerveja', 'vinho', 'caipirinha', 
            'drink', 'coca', 'pepsi', 'guaraná', 'fanta', 'sprite', 'heineken',
            'skol', 'brahma', 'stella', 'corona', 'budweiser', 'whisky', 'vodka',
            'cachaça', 'rum', 'gin', 'tequila', 'licor', 'champagne', 'espumante',
            'café', 'chá', 'cappuccino', 'expresso', 'latte', 'mocha', 'chocolate quente',
            'vitamina', 'smoothie', 'milk shake', 'açaí líquido', 'kombucha'
        ];
        return bebidas.some(bebida => nomeItem.toLowerCase().includes(bebida));
    }

    // Função para determinar se o pedido é do depósito
    function ehPedidoDeposito(pedido) {
        return pedido.itens.some(item => ehBebida(item.nome));
    }

    // Função para buscar pedidos
    async function buscarPedidos() {
        try {
            const response = await fetch('../api/buscar_pedidos_setor.php?setor=deposito');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            
            if (data.erro) {
                console.error('Erro da API:', data.erro);
                return;
            }

            exibirPedidos(data.pedidos || []);
        } catch (error) {
            console.error('Erro ao buscar pedidos:', error);
        }
    }

    // Função para exibir pedidos
    function exibirPedidos(pedidos) {
        // Limpar colunas
        Object.values(colunas).forEach(col => col.innerHTML = '');
        
        // Contar pedidos por status
        const contagem = {
            'Pendente': 0,
            'Em Preparo': 0,
            'Pronto': 0,
            'Finalizado': 0
        };

        // Filtrar apenas pedidos do depósito
        const pedidosDeposito = pedidos.filter(ehPedidoDeposito);

        pedidosDeposito.forEach(pedido => {
            contagem[pedido.status]++;
            
            const colunaId = `bebidas-${pedido.status.toLowerCase().replace(' ', '-')}`;
            const coluna = colunas[colunaId];
            
            if (coluna) {
                const cardPedido = criarCardPedido(pedido);
                coluna.appendChild(cardPedido);
            }
        });

        // Atualizar contadores
        contadores['bebidas-pendente'].textContent = contagem['Pendente'];
        contadores['bebidas-preparo'].textContent = contagem['Em Preparo'];
        contadores['bebidas-pronto'].textContent = contagem['Pronto'];
        contadores['bebidas-finalizado'].textContent = contagem['Finalizado'];

        // Mostrar estado vazio se necessário
        Object.keys(colunas).forEach(colunaId => {
            const coluna = colunas[colunaId];
            if (coluna.children.length === 0) {
                coluna.appendChild(criarEstadoVazio(colunaId));
            }
        });
    }

    // Função para criar card do pedido
    function criarCardPedido(pedido) {
        const card = document.createElement('div');
        card.className = 'pedido-card';
        card.dataset.pedidoId = pedido.id;

        const itensHtml = pedido.itens
            .filter(item => ehBebida(item.nome)) // Mostrar apenas bebidas
            .map(item => `
                <div class="item-pedido">
                    <span class="item-quantidade">${item.quantidade}x</span>
                    <span class="item-nome">${item.nome}</span>
                    <span class="item-preco">R$ ${parseFloat(item.preco).toFixed(2)}</span>
                </div>
            `).join('');

        let botaoAcao = '';
        if (pedido.status === 'Pendente') {
            botaoAcao = `<button class="btn btn-sm btn-warning w-100" onclick="atualizarStatus(${pedido.id}, 'Em Preparo')">
                <i class="bi bi-play-fill"></i> Iniciar Preparo
            </button>`;
        } else if (pedido.status === 'Em Preparo') {
            botaoAcao = `<button class="btn btn-sm btn-pronto-deposito w-100" onclick="atualizarStatus(${pedido.id}, 'Pronto')">
                <i class="bi bi-check-circle"></i> Marcar como Pronto
            </button>`;
        }

        card.innerHTML = `
            <div class="pedido-header">
                <div class="pedido-info">
                    <span class="pedido-numero">#${pedido.id}</span>
                    <span class="pedido-mesa">Mesa ${pedido.mesa_numero}</span>
                </div>
                <span class="pedido-tempo">${formatarTempo(pedido.data_hora)}</span>
            </div>
            <div class="pedido-itens">
                ${itensHtml}
            </div>
            <div class="pedido-total">
                <strong>Total: R$ ${parseFloat(pedido.valor_total).toFixed(2)}</strong>
            </div>
            <div class="pedido-acoes mt-2">
                ${botaoAcao}
            </div>
        `;

        return card;
    }

    // Função para criar estado vazio
    function criarEstadoVazio(colunaId) {
        const div = document.createElement('div');
        div.className = 'estado-vazio';
        
        const status = colunaId.split('-')[1];
        let mensagem = '';
        let icone = '';
        
        switch(status) {
            case 'pendente':
                mensagem = 'Nenhuma bebida pendente';
                icone = 'bi-clock';
                break;
            case 'preparo':
                mensagem = 'Nenhuma bebida em preparo';
                icone = 'bi-cup';
                break;
            case 'pronto':
                mensagem = 'Nenhuma bebida pronta';
                icone = 'bi-check-circle';
                break;
            case 'finalizado':
                mensagem = 'Nenhuma bebida finalizada';
                icone = 'bi-check-all';
                break;
        }
        
        div.innerHTML = `
            <i class="bi ${icone}"></i>
            <p>${mensagem}</p>
        `;
        
        return div;
    }

    // Função para formatar tempo
    function formatarTempo(dataHora) {
        const agora = new Date();
        const pedidoData = new Date(dataHora);
        const diffMs = agora - pedidoData;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'Agora';
        if (diffMins < 60) return `${diffMins}min`;
        
        const diffHoras = Math.floor(diffMins / 60);
        return `${diffHoras}h ${diffMins % 60}min`;
    }

    // Função global para atualizar status
    window.atualizarStatus = async function(pedidoId, novoStatus) {
        try {
            const response = await fetch('../api/atualizar_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `pedido_id=${pedidoId}&status=${encodeURIComponent(novoStatus)}`
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const result = await response.json();
            
            if (result.sucesso) {
                buscarPedidos(); // Recarregar pedidos
            } else {
                alert('Erro ao atualizar status: ' + (result.erro || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro ao atualizar status:', error);
            alert('Erro ao atualizar status do pedido');
        }
    };

    // Inicializar
    buscarPedidos();
    
    // Atualizar a cada 5 segundos
    setInterval(buscarPedidos, 5000);
});