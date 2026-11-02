<?php
/**
 * Estilos compartilhados dos PDFs de avaliação (Dompdf).
 *
 * O Dompdf não executa JS e tem suporte limitado a CSS (sem grid/flexbox/sticky).
 * Por isso usamos apenas tabelas + estilos inline e propriedades estáveis.
 */
?>
<style>
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        color: #1f2937;
        margin: 0;
        padding: 24px;
    }

    .header {
        border-bottom: 2px solid #2563eb;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }

    .header .clinic {
        font-size: 18px;
        font-weight: bold;
        color: #1d4ed8;
        margin: 0;
    }

    .header .doc-title {
        font-size: 13px;
        color: #374151;
        margin: 2px 0 0;
    }

    .meta {
        width: 100%;
        margin-bottom: 14px;
        border-collapse: collapse;
    }

    .meta td {
        padding: 3px 6px;
        vertical-align: top;
    }

    .meta .label {
        color: #6b7280;
        width: 140px;
    }

    h2 {
        font-size: 13px;
        color: #1d4ed8;
        margin: 18px 0 8px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 4px;
    }

    h3 {
        font-size: 12px;
        color: #1e40af;
        margin: 14px 0 6px;
    }

    .score-box {
        margin-top: 16px;
        padding: 10px 14px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        font-size: 13px;
        font-weight: bold;
        color: #1e40af;
    }

    .radar-block {
        margin-top: 8px;
        text-align: center;
    }

    .radar-block img.radar {
        width: 340px;
        height: auto;
    }

    .footer {
        margin-top: 24px;
        font-size: 10px;
        color: #9ca3af;
        text-align: center;
    }

    /* Tabelas genéricas (resumo / listas). */
    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    table.items th {
        background: #eff6ff;
        color: #1e40af;
        text-align: left;
        padding: 6px 8px;
        border: 1px solid #dbeafe;
        font-size: 10px;
    }

    table.items td {
        padding: 6px 8px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
    }

    table.items td.num {
        text-align: center;
        white-space: nowrap;
    }

    /* Quebra de página: permite quebrar dentro da seção, mas nunca no meio de uma linha. */
    .section-wrap {
        page-break-inside: auto;
    }

    .section-wrap table {
        page-break-inside: auto;
    }

    .section-wrap tr {
        page-break-inside: avoid;
    }

    /* Grids (heatmap). */
    table.grid {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.grid th,
    table.grid td {
        border: 1px solid #e5e7eb;
        padding: 3px 3px;
        text-align: center;
        font-size: 9px;
        overflow: hidden;
    }

    /* Quebra de palavra apenas nas células (códigos de rastreamento),
       nunca nos cabeçalhos — evita "Mand o"/"Ouvin te". */
    table.grid td {
        word-wrap: break-word;
    }

    table.grid th {
        background: #eff6ff;
        color: #1e40af;
        white-space: nowrap;
        font-size: 8px;
    }

    /* Marcos: 16 habilidades não cabem em uma única linha A4.
       Dividimos a matriz em blocos de 8 para manter os cabeçalhos legíveis. */
    table.grid.milestones {
        margin-bottom: 12px;
    }

    table.grid td.rowhead {
        background: #f9fafb;
        text-align: left;
        font-weight: bold;
    }

    .group-row td {
        background: #eef2ff;
        font-weight: bold;
        text-align: left;
        text-transform: uppercase;
        font-size: 9px;
        color: #374151;
    }

    .cell-1 {
        background-color: #22c55e;
    }

    .cell-0 {
        background-color: #e5e7eb;
    }

    .cell-empty {
        background-color: #f9fafb;
    }

    .legend {
        margin: 4px 0 8px;
        font-size: 9px;
        color: #6b7280;
    }

    .legend .swatch {
        display: inline-block;
        width: 10px;
        height: 10px;
        border: 1px solid #d1d5db;
        vertical-align: middle;
        margin-right: 2px;
    }

    .swatch.green {
        background-color: #22c55e;
    }

    .swatch.gray {
        background-color: #e5e7eb;
    }

    .swatch.empty {
        background-color: #f9fafb;
    }
</style>
