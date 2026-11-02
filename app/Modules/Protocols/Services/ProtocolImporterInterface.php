<?php

namespace App\Modules\Protocols\Services;

/**
 * ProtocolImporterInterface
 *
 * Contrato para importadores de protocolos (VB-MAPP, AFLS, etc.).
 *
 * Cada implementacao le um arquivo XLSX e devolve uma estrutura normalizada
 * (payload) pronta para ser persistida pelo ProtocolImportWriter.
 */
interface ProtocolImporterInterface
{
    /**
     * Extrai a estrutura normalizada de um arquivo XLSX.
     *
     * @return array{
     *     name: string,
     *     view_slug: string,
     *     description: string|null,
     *     levels: list<array{
     *         name: string,
     *         section: string,
     *         description: string|null,
     *         sort_order: int,
     *         skills: list<array{
     *             name: string,
     *             description: string|null,
     *             sort_order: int,
     *             items: list<array{
     *                 name: string,
     *                 description: string|null,
     *                 max_score: float,
     *                 sort_order: int,
     *                 metadata: array<string, mixed>
     *             }>
     *         }>
     *     }>
     * }
     */
    public function import(string $filePath): array;
}
