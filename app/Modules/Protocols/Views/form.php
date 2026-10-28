<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$isActive    = (string) old('is_active', '1');
$inputClass  = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30';
$labelClass  = 'mb-1 block text-sm font-medium text-gray-700';
$addBtnClass = 'inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100';
$delBtnClass = 'inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-red-600 transition hover:bg-red-50';
?>

<style>[x-cloak]{display:none!important}</style>

<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800"><?= esc($title) ?></h2>
            <p class="mt-1 text-sm text-gray-500">Monte a estrutura do protocolo (niveis, habilidades, itens e status).</p>
        </div>
        <a href="<?= site_url('protocols') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Voltar</a>
    </div>

    <?php $validationErrors = session('errors'); ?>
    <?php if (is_array($validationErrors) && $validationErrors !== []): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-1">
                <?php foreach ($validationErrors as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('protocols') ?>" method="post" x-data="protocolForm()" x-cloak class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <?= csrf_field() ?>

        <div class="flex flex-wrap border-b border-gray-200">
            <button type="button" @click="tab = 'geral'" :class="tab === 'geral' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Geral</button>
            <button type="button" @click="tab = 'status'" :class="tab === 'status' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Status</button>
            <button type="button" @click="tab = 'niveis'" :class="tab === 'niveis' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Níveis</button>
            <button type="button" @click="tab = 'habilidades'" :class="tab === 'habilidades' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Habilidades</button>
            <button type="button" @click="tab = 'itens'" :class="tab === 'itens' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Itens</button>
        </div>

        <div class="space-y-6 p-6">
            <div x-show="tab === 'geral'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="<?= $labelClass ?>">Nome *</label>
                        <input type="text" id="name" name="name" value="<?= esc(old('name')) ?>" required class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label for="is_active" class="<?= $labelClass ?>">Situação</label>
                        <select id="is_active" name="is_active" class="<?= $inputClass ?>">
                            <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label for="description" class="<?= $labelClass ?>">Descrição</label>
                    <textarea id="description" name="description" rows="3" class="<?= $inputClass ?>" placeholder="Ex: Protocolo de avaliacao de marcos do desenvolvimento."><?= esc(old('description')) ?></textarea>
                </div>
            </div>

            <div x-show="tab === 'status'">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-gray-600">Status usados nas avaliacoes e seus valores (ex: Nao Avaliado = 0, Certo = 1).</p>
                    <button type="button" @click="addStatus()" class="<?= $addBtnClass ?>">+ Adicionar Status</button>
                </div>
                <div class="space-y-3">
                    <template x-for="(status, sIndex) in statuses" :key="sIndex">
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_130px_auto] sm:items-center">
                                <input type="text" :name="'statuses[' + sIndex + '][name]'" x-model="status.name" placeholder="Nome do status" class="<?= $inputClass ?>">
                                <input type="number" :name="'statuses[' + sIndex + '][value]'" x-model="status.value" placeholder="Valor" class="<?= $inputClass ?>">
                                <button type="button" @click="removeStatus(sIndex)" class="<?= $delBtnClass ?>">Remover</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="statuses.length === 0" class="text-sm text-gray-500">Nenhum status adicionado.</p>
                </div>
            </div>

            <div x-show="tab === 'niveis'">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-gray-600">Niveis do protocolo (ex: Nivel 1, Nivel 2).</p>
                    <button type="button" @click="addLevel()" class="<?= $addBtnClass ?>">+ Adicionar Nível</button>
                </div>
                <div class="space-y-3">
                    <template x-for="(level, lIndex) in levels" :key="lIndex">
                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-700" x-text="'Nível ' + (lIndex + 1)"></span>
                                <button type="button" @click="removeLevel(lIndex)" class="<?= $delBtnClass ?>">Remover</button>
                            </div>
                            <div class="grid grid-cols-1 gap-3">
                                <input type="text" :name="'levels[' + lIndex + '][name]'" x-model="level.name" placeholder="Nome do nível" class="<?= $inputClass ?>">
                                <textarea :name="'levels[' + lIndex + '][description]'" x-model="level.description" rows="2" placeholder="Descrição (opcional)" class="<?= $inputClass ?>"></textarea>
                            </div>
                        </div>
                    </template>
                    <p x-show="levels.length === 0" class="text-sm text-gray-500">Nenhum nível adicionado.</p>
                </div>
            </div>

            <div x-show="tab === 'habilidades'">
                <p class="mb-3 text-sm text-gray-600">Habilidades agrupadas por nivel (ex: Mando, Tato).</p>
                <div class="space-y-6">
                    <template x-for="(level, lIndex) in levels" :key="lIndex">
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-gray-700" x-text="level.name || ('Nível ' + (lIndex + 1))"></h4>
                                <button type="button" @click="addSkill(lIndex)" class="<?= $addBtnClass ?>">+ Habilidade</button>
                            </div>
                            <div class="space-y-3">
                                <template x-for="(skill, sIndex) in level.skills" :key="sIndex">
                                    <div class="rounded-lg border border-gray-200 p-3">
                                        <div class="mb-2 flex items-center justify-between">
                                            <span class="text-xs font-medium text-gray-500" x-text="'Habilidade ' + (sIndex + 1)"></span>
                                            <button type="button" @click="removeSkill(lIndex, sIndex)" class="<?= $delBtnClass ?>">Remover</button>
                                        </div>
                                        <div class="grid grid-cols-1 gap-3">
                                            <input type="text" :name="'levels[' + lIndex + '][skills][' + sIndex + '][name]'" x-model="skill.name" placeholder="Nome da habilidade" class="<?= $inputClass ?>">
                                            <textarea :name="'levels[' + lIndex + '][skills][' + sIndex + '][description]'" x-model="skill.description" rows="2" placeholder="Descrição (opcional)" class="<?= $inputClass ?>"></textarea>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="level.skills.length === 0" class="text-sm text-gray-400">Nenhuma habilidade neste nível.</p>
                            </div>
                        </div>
                    </template>
                    <p x-show="levels.length === 0" class="text-sm text-gray-500">Adicione um nível antes de criar habilidades.</p>
                </div>
            </div>

            <div x-show="tab === 'itens'">
                <p class="mb-3 text-sm text-gray-600">Itens/perguntas agrupados por habilidade.</p>
                <div class="space-y-6">
                    <template x-for="(level, lIndex) in levels" :key="lIndex">
                        <div>
                            <template x-for="(skill, sIndex) in level.skills" :key="sIndex">
                                <div class="mb-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-700" x-text="(level.name || ('Nível ' + (lIndex + 1))) + ' › ' + (skill.name || ('Habilidade ' + (sIndex + 1)))"></h4>
                                        <button type="button" @click="addItem(lIndex, sIndex)" class="<?= $addBtnClass ?>">+ Item</button>
                                    </div>
                                    <div class="space-y-3">
                                        <template x-for="(item, iIndex) in skill.items" :key="iIndex">
                                            <div class="rounded-lg border border-gray-200 p-3">
                                                <div class="mb-2 flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-500" x-text="'Item ' + (iIndex + 1)"></span>
                                                    <button type="button" @click="removeItem(lIndex, sIndex, iIndex)" class="<?= $delBtnClass ?>">Remover</button>
                                                </div>
                                                <div class="grid grid-cols-1 gap-3">
                                                    <input type="text" :name="'levels[' + lIndex + '][skills][' + sIndex + '][items][' + iIndex + '][name]'" x-model="item.name" placeholder="Enunciado do item" class="<?= $inputClass ?>">
                                                    <textarea :name="'levels[' + lIndex + '][skills][' + sIndex + '][items][' + iIndex + '][description]'" x-model="item.description" rows="2" placeholder="Critério/descrição (opcional)" class="<?= $inputClass ?>"></textarea>
                                                </div>
                                            </div>
                                        </template>
                                        <p x-show="skill.items.length === 0" class="text-sm text-gray-400">Nenhum item nesta habilidade.</p>
                                    </div>
                                </div>
                            </template>
                            <p x-show="level.skills.length === 0" class="text-sm text-gray-400">Nenhuma habilidade neste nível.</p>
                        </div>
                    </template>
                    <p x-show="levels.length === 0" class="text-sm text-gray-500">Adicione um nível antes de criar itens.</p>
                </div>
            </div>




        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
            <a href="<?= site_url('protocols') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Salvar</button>
        </div>
    </form>
</div>

<script>
function protocolForm() {
    return {
        tab: 'geral',
        levels: <?= json_encode($levels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        statuses: <?= json_encode($statuses, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,

        addLevel() {
            this.levels.push({ name: '', description: '', skills: [] });
        },
        removeLevel(index) {
            this.levels.splice(index, 1);
        },
        addSkill(levelIndex) {
            this.levels[levelIndex].skills.push({ name: '', description: '', items: [] });
        },
        removeSkill(levelIndex, skillIndex) {
            this.levels[levelIndex].skills.splice(skillIndex, 1);
        },
        addItem(levelIndex, skillIndex) {
            this.levels[levelIndex].skills[skillIndex].items.push({ name: '', description: '' });
        },
        removeItem(levelIndex, skillIndex, itemIndex) {
            this.levels[levelIndex].skills[skillIndex].items.splice(itemIndex, 1);
        },
        addStatus() {
            this.statuses.push({ name: '', value: 0 });
        },
        removeStatus(index) {
            this.statuses.splice(index, 1);
        },
    };
}
</script>

<?= $this->endSection() ?>

