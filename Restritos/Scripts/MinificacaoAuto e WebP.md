# Configurador Online

Este repositório contém os arquivos do site.

## Minificação de CSS e JS

Foi adicionado um processo simples de minificação utilizando Node.js. Os arquivos `Layout.css` e `Layout.js` permanecem legíveis para manutenção, enquanto as versões minificadas são geradas automaticamente.

### Como gerar os arquivos minificados

1. Instale as dependências:
   ```bash
   npm install
   ```
2. Gere os arquivos manualmente:
   ```bash
   npm run build
   ```
   Isso cria `Layout/Estrutura/Layout.min.css` e `Layout/Estrutura/Layout.min.js`.
3. Para atualizar automaticamente sempre que os arquivos originais forem alterados:
   ```bash
   npm run watch
   ```
   O comando observa mudanças e executa a minificação.

## Conversão de imagens PNG para WebP

O script `convertImages.js` percorre a pasta `Layout/Imagens` e cria versões `.webp` para reduzir o tamanho das imagens.

1. Instale as dependências (caso ainda não o tenha feito):
   ```bash
   npm install
   ```
2. Execute a conversão manualmente:
   ```bash
   npm run convert-images
   ```

Os arquivos convertidos não são incluídos por padrão (estão em `.gitignore`).

## Servir imagens como WebP em tempo real

O script PHP `serveImage.php` permite converter PNG ou JPG para WebP na primeira requisição se o navegador oferecer suporte.
Inclua as imagens nas páginas utilizando URLs do tipo:

```html
<img src="/serveImage.php?file=Produtos/IBRQ/0IBRQ.png" alt="IBR Q">
```

Sempre que possível o arquivo convertido é armazenado em disco e cacheado por até 24 horas.