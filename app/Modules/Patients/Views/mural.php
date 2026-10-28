<div x-show="tab === 'mural'">
    <form @submit.prevent="submitPost()" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <label for="mural-content" class="mb-1 block text-sm font-medium text-gray-700">Nova publicação</label>
        <textarea
            id="mural-content"
            x-model="postContent"
            rows="3"
            placeholder="Escreva uma publicação no mural do paciente..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
        ></textarea>

        <div class="mt-3 flex flex-wrap items-center gap-3">
            <select
                x-model="postType"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
            >
                <template x-for="[value, label] in Object.entries(postTypes)" :key="value">
                    <option :value="value" x-text="label"></option>
                </template>
            </select>

            <button
                type="submit"
                :disabled="postSaving"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60"
            >
                <span x-show="!postSaving">Publicar</span>
                <span x-show="postSaving">Publicando...</span>
            </button>
        </div>

        <p x-show="postError" x-cloak x-text="postError" class="mt-2 text-sm text-red-600"></p>
    </form>

    <div class="mb-4 flex flex-wrap gap-2">
        <template x-for="filter in postTypeFilters" :key="filter.value">
            <button
                type="button"
                @click="postFilter = filter.value"
                :class="postFilter === filter.value ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="rounded-full px-3 py-1 text-xs font-medium transition"
                x-text="filter.label"
            ></button>
        </template>
    </div>

    <div x-show="filteredPosts.length === 0" class="flex flex-col items-center justify-center px-6 py-12 text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-700">Nenhuma publicação</p>
        <p class="mt-1 text-sm text-gray-500">Use o campo acima para registrar a primeira publicação no mural.</p>
    </div>

    <ol x-show="filteredPosts.length > 0" class="space-y-4">
        <template x-for="post in filteredPosts" :key="post.id">
            <li class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700" x-text="initials(post.user_name)"></div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800" x-text="post.user_name || 'Usuário'"></p>
                            <p class="text-xs text-gray-500" x-text="post.user_role || 'Profissional'"></p>
                        </div>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="typeBadge(post.type)" x-text="typeLabel(post.type)"></span>
                </div>

                <p class="mt-3 whitespace-pre-line text-sm text-gray-700" x-text="post.content"></p>
                <p class="mt-2 text-xs text-gray-400" x-text="formatDate(post.created_at)"></p>
            </li>
        </template>
    </ol>
</div>
