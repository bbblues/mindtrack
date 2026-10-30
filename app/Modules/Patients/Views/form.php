<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$isEdit = $patient !== null;
$p = $patient ?? [];
$formAction = $isEdit ? site_url('patients/update/' . (int) $p['id']) : site_url('patients');
$selectedUnitId = (string) old('unit_id', $p['unit_id'] ?? '');
$selectedInsuranceId = (string) old('health_insurance_id', $p['health_insurance_id'] ?? '');

$posts          = $posts ?? [];
$milestones     = $milestones ?? [];
$postTypes      = $postTypes ?? [];
$milestoneIcons = $milestoneIcons ?? [];
?>

<style>[x-cloak]{display:none!important}</style>

<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800"><?= esc($title) ?></h2>
            <p class="mt-1 text-sm text-gray-500">Preencha os dados do paciente.</p>
        </div>
        <a href="<?= site_url('patients') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Voltar</a>
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

    <form action="<?= $formAction ?>" method="post" x-data="patientForm()" class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <?= csrf_field() ?>

        <div class="flex border-b border-gray-200">
            <button type="button" @click="tab = 'pessoal'" :class="tab === 'pessoal' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Pessoal</button>
            <button type="button" @click="tab = 'clinico'" :class="tab === 'clinico' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Clínico</button>
            <button type="button" @click="tab = 'endereco'" :class="tab === 'endereco' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Endereço</button>
            <button type="button" @click="tab = 'responsaveis'" :class="tab === 'responsaveis' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Responsáveis</button>
            <?php if ($isEdit): ?>
                <button type="button" @click="tab = 'historico'" :class="tab === 'historico' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Histórico</button>
            <?php endif; ?>
        </div>

        <div class="space-y-6 p-6">
            <div x-show="tab === 'pessoal'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nome *</label>
                        <input type="text" id="name" name="name" value="<?= esc(old('name', $p['name'] ?? '')) ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="birth_date" class="mb-1 block text-sm font-medium text-gray-700">Data de Nascimento *</label>
                        <input type="date" id="birth_date" name="birth_date" value="<?= esc(old('birth_date', $p['birth_date'] ?? '')) ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="cpf" class="mb-1 block text-sm font-medium text-gray-700">CPF</label>
                        <input type="text" id="cpf" name="cpf" inputmode="numeric" placeholder="000.000.000-00" value="<?= esc(old('cpf', $p['cpf'] ?? '')) ?>" oninput="this.value = maskCpf(this.value)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="sex" class="mb-1 block text-sm font-medium text-gray-700">Sexo</label>
                        <select id="sex" name="sex" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <option value="">Selecione</option>
                            <option value="M" <?= old('sex', $p['sex'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= old('sex', $p['sex'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                            <option value="O" <?= old('sex', $p['sex'] ?? '') === 'O' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div>
                        <label for="is_active" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                        <select id="is_active" name="is_active" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <option value="1" <?= (int) old('is_active', $p['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= (int) old('is_active', $p['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <?php if ($isEdit): ?>
                    <div>
                        <label for="color" class="mb-1 block text-sm font-medium text-gray-700">Cor</label>
                        <input type="color" id="color" name="color" value="<?= esc(old('color', $p['color'] ?? '#3b82f6')) ?>" class="h-10 w-full cursor-pointer rounded-lg border border-gray-300 px-1 py-1 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-6 border-t border-gray-200 pt-5">
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">Vínculos</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="unit_id" class="mb-1 block text-sm font-medium text-gray-700">Unidade *</label>
                            <select id="unit_id" name="unit_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                                <option value="">Selecione a unidade</option>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?= (int) $unit['id'] ?>" <?= $selectedUnitId === (string) $unit['id'] ? 'selected' : '' ?>><?= esc($unit['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="health_insurance_id" class="mb-1 block text-sm font-medium text-gray-700">Convênio</label>
                            <select id="health_insurance_id" name="health_insurance_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                                <option value="">Sem convênio</option>
                                <?php foreach ($healthInsurances as $insurance): ?>
                                    <option value="<?= (int) $insurance['id'] ?>" <?= $selectedInsuranceId === (string) $insurance['id'] ? 'selected' : '' ?>><?= esc($insurance['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="insurance_number" class="mb-1 block text-sm font-medium text-gray-700">Número da Carteirinha</label>
                            <input type="text" id="insurance_number" name="insurance_number" value="<?= esc(old('insurance_number', $p['insurance_number'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                        <div>
                            <label for="tags" class="mb-1 block text-sm font-medium text-gray-700">Tags</label>
                            <input type="text" id="tags" name="tags" value="<?= esc(old('tags', $p['tags'] ?? '')) ?>" placeholder="Separadas por vírgula" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                    </div>
                </div>
            </div>
            <div x-show="tab === 'clinico'" x-cloak>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="diagnosis" class="mb-1 block text-sm font-medium text-gray-700">Diagnóstico</label>
                        <input type="text" id="diagnosis" name="diagnosis" value="<?= esc(old('diagnosis', $p['diagnosis'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="support_level" class="mb-1 block text-sm font-medium text-gray-700">Nível de Suporte</label>
                        <input type="text" id="support_level" name="support_level" value="<?= esc(old('support_level', $p['support_level'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="allergies" class="mb-1 block text-sm font-medium text-gray-700">Alergias</label>
                        <textarea id="allergies" name="allergies" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"><?= esc(old('allergies', $p['allergies'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'endereco'" x-cloak>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="zip_code" class="mb-1 block text-sm font-medium text-gray-700">CEP</label>
                        <input type="text" id="zip_code" name="zip_code" inputmode="numeric" placeholder="00000-000" value="<?= esc(old('zip_code', $p['zip_code'] ?? '')) ?>" oninput="this.value = maskCep(this.value)" onblur="fetchCep(this.value)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        <p id="cep-status" class="mt-1 text-xs text-blue-600"></p>
                    </div>
                    <div>
                        <label for="street" class="mb-1 block text-sm font-medium text-gray-700">Rua</label>
                        <input type="text" id="street" name="street" value="<?= esc(old('street', $p['street'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="number" class="mb-1 block text-sm font-medium text-gray-700">Número</label>
                        <input type="text" id="number" name="number" value="<?= esc(old('number', $p['number'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="complement" class="mb-1 block text-sm font-medium text-gray-700">Complemento</label>
                        <input type="text" id="complement" name="complement" value="<?= esc(old('complement', $p['complement'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="neighborhood" class="mb-1 block text-sm font-medium text-gray-700">Bairro</label>
                        <input type="text" id="neighborhood" name="neighborhood" value="<?= esc(old('neighborhood', $p['neighborhood'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="city" class="mb-1 block text-sm font-medium text-gray-700">Cidade</label>
                        <input type="text" id="city" name="city" value="<?= esc(old('city', $p['city'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="state" class="mb-1 block text-sm font-medium text-gray-700">UF</label>
                        <input type="text" id="state" name="state" maxlength="2" value="<?= esc(old('state', $p['state'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                </div>
            </div>
            <div x-show="tab === 'responsaveis'" x-cloak class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">Responsáveis</h3>
                        <p class="text-xs text-gray-500">Marque um responsável como Principal e outro como Financeiro.</p>
                    </div>
                    <button type="button" @click="addGuardian()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Responsável</button>
                </div>

                <template x-for="(g, index) in guardians" :key="index">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400" x-text="'Responsável ' + (index + 1)"></span>
                            <button type="button" @click="removeGuardian(index)" class="text-sm font-medium text-red-600 transition hover:text-red-700">Remover</button>
                        </div>

                        <input type="hidden" :name="'guardians[' + index + '][id]'" :value="g.id">

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">Nome *</label>
                                <input type="text" :name="'guardians[' + index + '][name]'" x-model="g.name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">Parentesco</label>
                                <input type="text" :name="'guardians[' + index + '][relationship]'" x-model="g.relationship" placeholder="Pai, Mãe, ..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">CPF</label>
                                <input type="text" :name="'guardians[' + index + '][cpf]'" x-model="g.cpf" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">Telefone</label>
                                <input type="text" :name="'guardians[' + index + '][phone]'" x-model="g.phone" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-700">E-mail</label>
                                <input type="email" :name="'guardians[' + index + '][email]'" x-model="g.email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            </div>
                            <div class="flex gap-6 sm:col-span-2">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" value="1" :name="'guardians[' + index + '][is_primary]'" :checked="g.is_primary == 1" @change="g.is_primary = $event.target.checked ? 1 : 0" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    Principal
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" value="1" :name="'guardians[' + index + '][is_financial]'" :checked="g.is_financial == 1" @change="g.is_financial = $event.target.checked ? 1 : 0" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    Financeiro
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <p x-show="guardians.length === 0" class="text-sm text-gray-500">Nenhum responsável adicionado.</p>
            </div>

            <?php if ($isEdit): ?>
            <div x-show="tab === 'historico'" x-cloak>
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">Histórico de Evoluções</h3>
                        <p class="text-xs text-gray-500">Feed cronológico das sessões registradas.</p>
                    </div>
                    <a href="<?= site_url('evolutions/' . (int) ($p['id'] ?? 0)) ?>" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Ver histórico completo</a>
                </div>
                <?= view('App\Modules\Evolutions\Views\_feed', ['evolutions' => $evolutions]) ?>
            </div>
            <?php endif; ?>



        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
            <a href="<?= site_url('patients') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Salvar</button>
        </div>
    </form>

    <?php if ($isEdit): ?>
    <div x-data="patientFeed()" x-cloak class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex border-b border-gray-200">
            <button type="button" @click="tab = 'mural'" :class="tab === 'mural' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Mural</button>
            <button type="button" @click="tab = 'jornada'" :class="tab === 'jornada' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="border-b-2 px-4 py-3 text-sm font-medium">Jornada</button>
        </div>

        <div class="p-6">
            <?= view('App\Modules\Patients\Views\mural', [
                'patient'   => $patient,
                'posts'     => $posts,
                'postTypes' => $postTypes,
            ]) ?>
            <?= view('App\Modules\Patients\Views\jornada', [
                'patient'        => $patient,
                'milestones'     => $milestones,
                'milestoneIcons' => $milestoneIcons,
            ]) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function maskCpf(value) {
    return (value || '')
        .replace(/\D/g, '')
        .slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

function maskCep(value) {
    return (value || '')
        .replace(/\D/g, '')
        .slice(0, 8)
        .replace(/(\d{5})(\d)/, '$1-$2');
}

async function fetchCep(value) {
    const cep = (value || '').replace(/\D/g, '');
    const status = document.getElementById('cep-status');

    if (cep.length !== 8) {
        return;
    }

    if (status) {
        status.textContent = 'Buscando...';
    }

    try {
        const response = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
        const data = await response.json();

        if (data.erro) {
            if (status) status.textContent = 'CEP não encontrado';
            return;
        }

        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        set('street', data.logradouro);
        set('neighborhood', data.bairro);
        set('city', data.localidade);
        set('state', data.uf);

        if (status) {
            status.textContent = '';
        }

        const number = document.getElementById('number');
        if (number) number.focus();
    } catch (error) {
        if (status) status.textContent = 'Erro ao buscar o CEP';
    }
}

function patientForm() {
    return {
        tab: 'pessoal',
        guardians: <?= json_encode($guardians, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        addGuardian() {
            this.guardians.push({ id: 0, name: '', relationship: '', cpf: '', phone: '', email: '', is_primary: 0, is_financial: 0 });
        },
        removeGuardian(index) {
            this.guardians.splice(index, 1);
        },
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const cpf = document.getElementById('cpf');
    if (cpf && cpf.value) cpf.value = maskCpf(cpf.value);

    const zip = document.getElementById('zip_code');
    if (zip && zip.value) zip.value = maskCep(zip.value);
});
</script>

<?php if ($isEdit): ?>
<script>
function patientFeed() {
    return {
        tab: 'mural',
        token: <?= json_encode(csrf_hash()) ?>,
        patientId: <?= (int) ($p['id'] ?? 0) ?>,
        storePostUrl: <?= json_encode(site_url('patients/' . (int) ($p['id'] ?? 0) . '/posts')) ?>,
        storeMilestoneUrl: <?= json_encode(site_url('patients/' . (int) ($p['id'] ?? 0) . '/milestones')) ?>,

        posts: <?= json_encode($posts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        postTypes: <?= json_encode($postTypes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        postFilter: 'all',
        postContent: '',
        postType: 'evolution',
        postSaving: false,
        postError: '',

        milestones: <?= json_encode($milestones, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        milestoneIcons: <?= json_encode($milestoneIcons, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        milestoneOpen: false,
        milestoneTitle: '',
        milestoneDescription: '',
        milestoneEventDate: <?= json_encode(date('Y-m-d')) ?>,
        milestoneIcon: '🎯',
        milestoneSaving: false,
        milestoneError: '',

        get postTypeFilters() {
            return [
                { value: 'all', label: 'Todos' },
                ...Object.entries(this.postTypes).map(([value, label]) => ({ value, label })),
            ];
        },

        get filteredPosts() {
            if (this.postFilter === 'all') return this.posts;
            return this.posts.filter(post => post.type === this.postFilter);
        },

        get milestoneGroups() {
            const sorted = [...this.milestones].sort((a, b) => String(b.event_date || '').localeCompare(String(a.event_date || '')));
            const groups = [];
            const map = new Map();

            for (const milestone of sorted) {
                const key = this.monthKey(milestone.event_date);
                if (! map.has(key)) {
                    const group = { key, label: this.monthLabel(milestone.event_date), items: [] };
                    map.set(key, group);
                    groups.push(group);
                }
                map.get(key).items.push(milestone);
            }

            return groups;
        },

        typeLabel(type) {
            return this.postTypes[type] || type;
        },

        typeBadge(type) {
            const map = {
                evolution: 'bg-blue-100 text-blue-700',
                communication: 'bg-purple-100 text-purple-700',
                family: 'bg-green-100 text-green-700',
                school: 'bg-amber-100 text-amber-700',
            };

            return map[type] || 'bg-gray-100 text-gray-700';
        },

        initials(name) {
            return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || '?';
        },

        formatDate(value) {
            if (! value) return '—';
            const date = new Date(String(value).replace(' ', 'T'));
            if (isNaN(date.getTime())) return value;
            const day = date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const time = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            return day + ' ' + time;
        },

        formatDay(value) {
            if (! value) return '—';
            const date = new Date(String(value) + 'T00:00:00');
            if (isNaN(date.getTime())) return value;
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        monthKey(value) {
            if (! value) return '—';
            const date = new Date(String(value) + 'T00:00:00');
            if (isNaN(date.getTime())) return '—';
            return date.getFullYear() + '-' + date.getMonth();
        },

        monthLabel(value) {
            if (! value) return '—';
            const date = new Date(String(value) + 'T00:00:00');
            if (isNaN(date.getTime())) return value;
            const month = date.toLocaleDateString('pt-BR', { month: 'long' });
            return month.charAt(0).toUpperCase() + month.slice(1) + ' de ' + date.getFullYear();
        },

        refreshCsrf(hash) {
            if (! hash) return;
            this.token = hash;
            document.querySelectorAll('input[name="csrf_test_name"]').forEach(el => { el.value = hash; });
        },

        async submitPost() {
            this.postError = '';
            const content = this.postContent.trim();
            if (! content) {
                this.postError = 'Escreva o conteúdo da publicação.';
                return;
            }

            this.postSaving = true;
            try {
                const response = await fetch(this.storePostUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ patient_id: this.patientId, content, type: this.postType }),
                });

                const data = await response.json();
                this.refreshCsrf(data.csrf_hash);

                if (! data.success) {
                    this.postError = data.message || 'Erro ao publicar no mural.';
                    return;
                }

                if (data.post) this.posts.unshift(data.post);
                this.postContent = '';
                this.postFilter = 'all';
            } catch (error) {
                this.postError = 'Erro ao publicar no mural.';
            } finally {
                this.postSaving = false;
            }
        },

        async submitMilestone() {
            this.milestoneError = '';
            const title = this.milestoneTitle.trim();
            if (! title) {
                this.milestoneError = 'Informe o título do marco.';
                return;
            }
            if (! this.milestoneEventDate) {
                this.milestoneError = 'Informe a data do marco.';
                return;
            }

            this.milestoneSaving = true;
            try {
                const response = await fetch(this.storeMilestoneUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        patient_id: this.patientId,
                        title,
                        description: this.milestoneDescription.trim(),
                        event_date: this.milestoneEventDate,
                        icon: this.milestoneIcon,
                    }),
                });

                const data = await response.json();
                this.refreshCsrf(data.csrf_hash);

                if (! data.success) {
                    this.milestoneError = data.message || 'Erro ao salvar o marco.';
                    return;
                }

                if (data.milestone) this.milestones.unshift(data.milestone);
                this.milestoneTitle = '';
                this.milestoneDescription = '';
                this.milestoneEventDate = new Date().toISOString().slice(0, 10);
                this.milestoneIcon = '🎯';
                this.milestoneOpen = false;
            } catch (error) {
                this.milestoneError = 'Erro ao salvar o marco.';
            } finally {
                this.milestoneSaving = false;
            }
        },
    };
}
</script>
<?php endif; ?>

<?= $this->endSection() ?>

