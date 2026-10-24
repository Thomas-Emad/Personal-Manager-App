<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BookOpen, 
    Plus, 
    ExternalLink, 
    FileText, 
    Tag, 
    Search, 
    Trash2, 
    FolderGit2, 
    Copy, 
    Check, 
    Globe,
    Filter
} from 'lucide-vue-next';

interface ResourceAttachment {
    id: string;
    file_name: string;
    file_path: string;
    mime_type: string;
    file_size_bytes: number;
}

interface ResourceItem {
    id: string;
    roadmap_id: string | null;
    topic_id: string | null;
    task_id: string | null;
    title: string;
    resource_type: 'url' | 'note' | 'text';
    category: string;
    content_or_url: string;
    created_at: string;
    roadmap?: {
        id: string;
        title: string;
    } | null;
    topic?: {
        id: string;
        title: string;
    } | null;
    task?: {
        id: string;
        title: string;
    } | null;
    attachments: ResourceAttachment[];
}

interface TopicItem {
    id: string;
    title: string;
}

interface PhaseItem {
    id: string;
    title: string;
    topics: TopicItem[];
}

interface RoadmapWithPhases {
    id: string;
    title: string;
    phases: PhaseItem[];
}

const props = defineProps<{
    resources: ResourceItem[];
    roadmaps: RoadmapWithPhases[];
    categories: string[];
    currentCategory: string;
}>();

const page = usePage();
const isArabic = computed(() => page.props.locale === 'ar');

const showAddModal = ref(false);
const searchQuery = ref('');
const copiedId = ref<string | null>(null);

const defaultCategories = ['documentation', 'tutorial', 'video', 'book', 'article', 'notes', 'cheat_sheet'];
const allCategories = computed(() => {
    const set = new Set([...defaultCategories, ...props.categories]);
    return Array.from(set);
});

// Filter resources based on selected category and search query
const filteredResources = computed(() => {
    let list = props.resources;
    if (props.currentCategory && props.currentCategory !== 'all') {
        list = list.filter(r => r.category.toLowerCase() === props.currentCategory.toLowerCase());
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(r => 
            r.title.toLowerCase().includes(q) || 
            r.category.toLowerCase().includes(q) || 
            r.content_or_url.toLowerCase().includes(q) ||
            r.roadmap?.title.toLowerCase().includes(q) ||
            r.topic?.title.toLowerCase().includes(q)
        );
    }
    return list;
});

const selectCategoryFilter = (cat: string) => {
    router.get('/library', { category: cat }, { preserveState: true, preserveScroll: true });
};

// Form for adding a new resource
const form = useForm({
    title: '',
    resource_type: 'url' as 'url' | 'note' | 'text',
    category: 'documentation',
    content_or_url: '',
    roadmap_id: '' as string | null,
    topic_id: '' as string | null,
});

// Computed list of topics for currently selected roadmap in modal
const availableTopics = computed(() => {
    if (!form.roadmap_id) return [];
    const rm = props.roadmaps.find(r => r.id === form.roadmap_id);
    if (!rm) return [];
    const topics: TopicItem[] = [];
    rm.phases.forEach(p => {
        if (p.topics && p.topics.length) {
            topics.push(...p.topics);
        }
    });
    return topics;
});

const submitResource = () => {
    form.post('/library', {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const deleteResource = (id: string, title: string) => {
    if (confirm(isArabic.value ? `هل تريد بالتأكيد حذف "${title}"؟` : `Delete resource "${title}"?`)) {
        router.delete(`/library/${id}`, { preserveScroll: true });
    }
};

const copyContent = (resource: ResourceItem) => {
    navigator.clipboard.writeText(resource.content_or_url);
    copiedId.value = resource.id;
    setTimeout(() => {
        if (copiedId.value === resource.id) {
            copiedId.value = null;
        }
    }, 2000);
};

const getCategoryColor = (cat: string) => {
    switch (cat.toLowerCase()) {
        case 'documentation':
        case 'docs':
            return 'bg-blue-900/40 text-blue-300 border-blue-700/50';
        case 'video':
            return 'bg-rose-900/40 text-rose-300 border-rose-700/50';
        case 'book':
            return 'bg-emerald-900/40 text-emerald-300 border-emerald-700/50';
        case 'tutorial':
            return 'bg-amber-900/40 text-amber-300 border-amber-700/50';
        case 'notes':
            return 'bg-purple-900/40 text-purple-300 border-purple-700/50';
        default:
            return 'bg-slate-800 text-slate-300 border-slate-700';
    }
};
</script>

<template>
    <AppLayout :title="isArabic ? 'مكتبة التعلم' : 'Study Library'">
        <div class="space-y-5">
            
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-white flex items-center gap-2">
                        <BookOpen class="w-6 h-6 text-indigo-400" />
                        <span>{{ isArabic ? 'مكتبة التعلم والمراجع' : 'Study Library' }}</span>
                    </h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ isArabic ? 'إدارة المراجع والملاحظات والروابط التعليمية لجميع المسارات' : 'Manage study resources, external links, and markdown notes' }}
                    </p>
                </div>

                <button
                    @click="showAddModal = true"
                    class="px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-1.5 shadow-lg shadow-indigo-600/30 transition active:scale-95"
                >
                    <Plus class="w-4 h-4" />
                    <span>{{ isArabic ? 'إضافة مادة' : 'Add Resource' }}</span>
                </button>
            </div>

            <!-- Search and Filter Bar -->
            <div class="space-y-3">
                <div class="relative">
                    <Search class="w-4 h-4 text-slate-400 absolute top-3 ltr:left-3 rtl:right-3" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="isArabic ? 'بحث في العناوين أو المحتوى أو الفئات...' : 'Search resources by title, category, or content...'"
                        class="w-full bg-slate-900/90 border border-slate-800 rounded-xl ltr:pl-9 rtl:pr-9 pr-4 py-2.5 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition"
                    />
                </div>

                <!-- Categories Horizontal Scroll -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
                    <button
                        @click="selectCategoryFilter('all')"
                        :class="currentCategory === 'all' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800'"
                        class="px-3 py-1.5 rounded-lg whitespace-nowrap transition"
                    >
                        {{ isArabic ? 'الكل' : 'All' }}
                    </button>
                    <button
                        v-for="cat in allCategories"
                        :key="cat"
                        @click="selectCategoryFilter(cat)"
                        :class="currentCategory === cat ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800'"
                        class="px-3 py-1.5 rounded-lg whitespace-nowrap capitalize transition"
                    >
                        {{ cat.replace('_', ' ') }}
                    </button>
                </div>
            </div>

            <!-- Resources List -->
            <div v-if="filteredResources.length > 0" class="space-y-3">
                <div
                    v-for="resource in filteredResources"
                    :key="resource.id"
                    class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 transition hover:border-slate-700 shadow-sm space-y-3"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                                :class="resource.resource_type === 'url' ? 'bg-indigo-950/80 text-indigo-400 border border-indigo-800/40' : 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/40'"
                            >
                                <Globe v-if="resource.resource_type === 'url'" class="w-4 h-4" />
                                <FileText v-else class="w-4 h-4" />
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-white leading-snug">
                                    {{ resource.title }}
                                </h3>
                                <div class="flex items-center flex-wrap gap-2 mt-1">
                                    <span 
                                        :class="getCategoryColor(resource.category)"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-medium border"
                                    >
                                        {{ resource.category }}
                                    </span>

                                    <span v-if="resource.roadmap" class="flex items-center gap-1 text-[10px] text-slate-400">
                                        <FolderGit2 class="w-3 h-3 text-slate-500" />
                                        <span>{{ resource.roadmap.title }}</span>
                                    </span>

                                    <span v-if="resource.topic" class="text-[10px] text-indigo-400">
                                        / {{ resource.topic.title }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1">
                            <button
                                @click="copyContent(resource)"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                :title="isArabic ? 'نسخ الرابط / المحتوى' : 'Copy URL/Content'"
                            >
                                <Check v-if="copiedId === resource.id" class="w-4 h-4 text-emerald-400" />
                                <Copy v-else class="w-4 h-4" />
                            </button>

                            <button
                                @click="deleteResource(resource.id, resource.title)"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-950/40 transition"
                                :title="isArabic ? 'حذف' : 'Delete'"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Content display -->
                    <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3 text-xs text-slate-300 break-words">
                        <div v-if="resource.resource_type === 'url'" class="flex items-center justify-between gap-2">
                            <span class="truncate font-mono text-[11px] text-indigo-300">{{ resource.content_or_url }}</span>
                            <a
                                :href="resource.content_or_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white text-[11px] font-semibold transition shrink-0"
                            >
                                <span>{{ isArabic ? 'فتح' : 'Open' }}</span>
                                <ExternalLink class="w-3 h-3" />
                            </a>
                        </div>
                        <div v-else class="whitespace-pre-line leading-relaxed font-sans text-xs">
                            {{ resource.content_or_url }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-slate-900/60 border border-dashed border-slate-800 rounded-2xl p-8 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-950/50 text-indigo-400 border border-indigo-800/50 mx-auto flex items-center justify-center">
                    <BookOpen class="w-6 h-6" />
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white">
                        {{ isArabic ? 'لا توجد مراجع هنا' : 'No study resources found' }}
                    </h4>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        {{ isArabic ? 'أضف روابط توثيقية، دروس، أو ملاحظات شخصية لتنظيم مصادر تعلمك في مكان واحد.' : 'Add external documentation, video tutorials, or markdown notes linked to your roadmaps.' }}
                    </p>
                </div>
                <button
                    @click="showAddModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold inline-flex items-center gap-1.5 shadow-md shadow-indigo-600/30 transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>{{ isArabic ? 'إضافة أول مادة' : 'Add First Resource' }}</span>
                </button>
            </div>

            <!-- Add Resource Modal -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-5 space-y-4 shadow-2xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Plus class="w-4 h-4 text-indigo-400" />
                            <span>{{ isArabic ? 'إضافة مادة تعليمية جديدة' : 'Add Learning Resource' }}</span>
                        </h3>
                        <button @click="showAddModal = false" class="text-slate-400 hover:text-white text-xs">✕</button>
                    </div>

                    <form @submit.prevent="submitResource" class="space-y-3.5">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                {{ isArabic ? 'العنوان' : 'Title' }} *
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                :placeholder="isArabic ? 'مثال: توثيق Laravel الرسمي' : 'e.g. Official Documentation'"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">
                                    {{ isArabic ? 'النوع' : 'Type' }}
                                </label>
                                <select
                                    v-model="form.resource_type"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="url">{{ isArabic ? 'رابط خارجي (URL)' : 'External URL' }}</option>
                                    <option value="note">{{ isArabic ? 'ملاحظة شخصية' : 'Personal Note' }}</option>
                                    <option value="text">{{ isArabic ? 'محتوى نصي' : 'Text Content' }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">
                                    {{ isArabic ? 'الفئة' : 'Category' }}
                                </label>
                                <input
                                    v-model="form.category"
                                    type="text"
                                    required
                                    placeholder="docs, video, tutorial..."
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                {{ form.resource_type === 'url' ? (isArabic ? 'الرابط الإلكتروني' : 'Resource URL') : (isArabic ? 'المحتوى / الملاحظات' : 'Content / Notes') }} *
                            </label>
                            <textarea
                                v-model="form.content_or_url"
                                required
                                rows="3"
                                :placeholder="form.resource_type === 'url' ? 'https://example.com/docs' : (isArabic ? 'اكتب ملاحظاتك هنا...' : 'Write your notes or summary here...')"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">
                                    {{ isArabic ? 'المسار المرتبط (اختياري)' : 'Roadmap (Optional)' }}
                                </label>
                                <select
                                    v-model="form.roadmap_id"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option :value="null">{{ isArabic ? 'غير مرتبط بمسار' : 'None' }}</option>
                                    <option v-for="rm in roadmaps" :key="rm.id" :value="rm.id">
                                        {{ rm.title }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">
                                    {{ isArabic ? 'الموضوع (اختياري)' : 'Topic (Optional)' }}
                                </label>
                                <select
                                    v-model="form.topic_id"
                                    :disabled="!availableTopics.length"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 disabled:opacity-50"
                                >
                                    <option :value="null">{{ isArabic ? 'غير مرتبط' : 'None' }}</option>
                                    <option v-for="top in availableTopics" :key="top.id" :value="top.id">
                                        {{ top.title }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="px-3 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs hover:bg-slate-700 transition"
                            >
                                {{ isArabic ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition disabled:opacity-50"
                            >
                                {{ isArabic ? 'حفظ المادة' : 'Save Resource' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
