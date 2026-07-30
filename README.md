# Kaua + Debora — site de casamento

Uma recriação independente e inteiramente offline de uma página de casamento com tema floral. O projeto foi desenvolvido do zero com HTML5, CSS e JavaScript ES6 puros.

## Como abrir

Abra `index.html` diretamente no navegador ou sirva a pasta em qualquer servidor local. Não há etapa de instalação, pacote, compilação ou dependência externa.

## Recursos incluídos

- Navegação responsiva com menu móvel
- Contagem regressiva para 17 de agosto de 2026
- Seções do casal, padrinhos, cerimônia e recepção
- Galeria ilustrada com navegação, rotação automática e ampliação
- Lista de presentes com ordenação, revelação de itens e carrinho
- Limite de três itens no carrinho e finalização demonstrativa local
- Formulário de confirmação de presença e mural de recados
- Persistência de carrinho, RSVP e recados no `localStorage` do navegador
- Animações de entrada leves e suporte a redução de movimento

## Estrutura

```text
.
├── index.html
├── css/
│   └── style.css
├── js/
│   └── script.js
├── img/
│   ├── floral-corner.svg
│   └── gallery-1.svg ... gallery-5.svg
├── assets/
├── fonts/
│   └── README.md
└── README.md
```

## Funcionamento offline

Todos os recursos visuais são SVGs locais e não são carregados scripts, fontes, imagens, rastreadores, bibliotecas, APIs ou serviços de terceiros. A função de copiar endereço usa a área de transferência quando o navegador permitir; caso contrário, apresenta o endereço na tela.

Os dados preenchidos nos formulários são somente demonstrativos e ficam restritos ao navegador local por meio de `localStorage`. Para reiniciar a demonstração, limpe os dados do site no navegador.
