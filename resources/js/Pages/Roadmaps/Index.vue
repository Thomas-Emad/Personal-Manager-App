<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Map, 
    Plus, 
    Upload, 
    ChevronRight, 
    ChevronLeft,
    CheckCircle2, 
    Layers, 
    Clock, 
    Trash2 
} from 'lucide-vue-next';

interface RoadmapItem {
    id: string;
    title: string;
    slug: string;
    description: string | null;
    status: string;
    sort_order: number;
    created_at: string;
    progress: {
        total_tasks: number;
        completed_tasks: number;
        completion_rate: number;
        estimated_minutes_total: number;
        estimated_minutes_completed: number;
    };
}

const props = defineProps<{
    roadmaps: RoadmapItem[];
}>();

const page = usePage();
const isArabic = computed(() => page.props.locale === 'ar');

const showCreateModal = ref(false);
const showImportModal = ref(false);

const roadmapForm = useForm({
    title: '',
    description: '',
});

const submitRoadmap = () => {
    roadmapForm.post('/roadmaps', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            roadmapForm.reset();
        },
    });
};

const deleteRoadmap = (id: string, title: string) => {
    if (confirm(isArabic.value ? `هل تريد حذف المسار "${title}" وجميع مراحله ومهامه؟` : `Delete roadmap "${title}" and all its contents?`)) {
        router.delete(`/roadmaps/${id}`, { preserveScroll: true });
    }
};

const jsonFile = ref<File | null>(null);
const importForm = useForm({
    json_file: null as File | null,
});

const submitImport = () => {
    if (!importForm.json_file) return;
    importForm.post('/settings/roadmap/import', {
        preserveScroll: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <AppLayout :title="isArabic ? 'مسارات التعلم' : 'Learning Roadmaps'">
        <div class="space-y-4">
            
            <!-- Header Bar -->
            <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md">
                <div>
                    <h2 class="text-base font-bold text-white">{{ isArabic ? 'مسارات التعلم المخصصة' : 'Learning Roadmaps' }}</h2>
                    <p class="text-xs text-slate-400">
                        {{ isArabic ? 'أنشئ ونظم مساراتك ومراحلها ومهامها بشكل ديناميكي كامل.' : 'Design dynamic roadmaps, phases, lessons, and tasks.' }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Import Button -->
                    <button
                        @click="showImportModal = true"
                        type="button"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                        :title="isArabic ? 'استيراد مسار (JSON)' : 'Import Roadmap (JSON)'"
                    >
                        <Upload class="w-4 h-4 text-indigo-400" />
                    </button>

                    <!-- New Roadmap Button -->
                    <button
                        @click="showCreateModal = true"
                        type="button"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-3 py-2 rounded-xl text-xs flex items-center gap-1.5 transition shadow-lg shadow-indigo-600/30"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ isArabic ? 'مسار جديد' : 'New Roadmap' }}</span>
                    </button>
                </div>
            </div>

            <!-- Roadmaps Grid -->
            <div class="space-y-3">
                <div
                    v-for="r in roadmaps"
                    :key="r.id"
                    class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 hover:border-slate-700 transition"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <Link :href="`/roadmaps/${r.id}`" class="text-base font-bold text-white hover:text-indigo-400 transition">
                                {{ r.title }}
                            </Link>
                            <p v-if="r.description" class="text-xs text-slate-400 line-clamp-2">{{ r.description }}</p>
                        </div>
                        <button
                            @click="deleteRoadmap(r.id, r.title)"
                            type="button"
                            class="text-slate-500 hover:text-rose-400 p-1.5 rounded-lg hover:bg-rose-500/10 transition"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">
                                {{ r.progress.completed_tasks }} / {{ r.progress.total_tasks }} {{ isArabic ? 'مهام مكتملة' : 'tasks completed' }}
                            </span>
                            <span class="font-bold text-indigo-400 font-mono">{{ r.progress.completion_rate }}%</span>
                        </div>
                        <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
                            <div 
                                class="bg-indigo-600 h-full rounded-full transition-all duration-500" 
                                :style="{ width: `${r.progress.completion_rate}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Actions & Footer -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 text-xs">
                        <span class="text-slate-500 flex items-center gap-1 font-mono">
                            <Clock class="w-3.5 h-3.5 text-indigo-400" />
                            {{ Math.round(r.progress.estimated_minutes_total / 60) }} {{ isArabic ? 'ساعة تقديرية' : 'estimated hrs' }}
                        </span>

                        <Link
                            :href="`/roadmaps/${r.id}`"
                            class="bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white font-medium px-3 py-1.5 rounded-xl transition flex items-center gap-1"
                        >
                            <span>{{ isArabic ? 'إدارة المسار والمراحل' : 'Open Builder' }}</span>
                            <component :is="isArabic ? ChevronLeft : ChevronRight" class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                <div v-if="roadmaps.length === 0" class="text-center py-12 text-slate-500 text-xs border border-dashed border-slate-800 rounded-2xl">
                    {{ isArabic ? 'لا توجد مسارات حتى الآن. أنشئ مسارك الأول أو استورد مساراً جاهزاً!' : 'No roadmaps created yet. Click New Roadmap above to begin!' }}
                </div>
            </div>

            <!-- Create Roadmap Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="font-bold text-sm text-white">{{ isArabic ? 'إنشاء مسار تعلم جديد' : 'Create Learning Roadmap' }}</h3>
                        <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">✕</button>
                    </div>

                    <form @submit.prevent="submitRoadmap" class="space-y-3 text-xs">
                        <div>
                            <label class="block text-slate-400 mb-1">{{ isArabic ? 'عنوان المسار' : 'Roadmap Title' }}</label>
                            <input
                                v-model="roadmapForm.title"
                                type="text"
                                :placeholder="isArabic ? 'مثال: هندسة البرمجيات المتقدمة' : 'e.g., Advanced Software Architecture'"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1">{{ isArabic ? 'الوصف والأهداف' : 'Description & Objectives' }}</label>
                            <textarea
                                v-model="roadmapForm.description"
                                rows="3"
                                :placeholder="isArabic ? 'وصف مختصر لأهداف المسار...' : 'Brief description of the learning goals...'"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
                            ></textarea>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button
                                @click="showCreateModal = false"
                                type="button"
                                class="px-4 py-2 rounded-xl text-slate-400 hover:text-white"
                            >
                                {{ isArabic ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button
                                type="submit"
                                :disabled="roadmapForm.processing"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2 rounded-xl"
                            >
                                {{ isArabic ? 'إنشاء المسار' : 'Create Roadmap' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import Roadmap Modal -->
            <div v-if="showImportModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="font-bold text-sm text-white">{{ isArabic ? 'استيراد مسار (ملف JSON)' : 'Import Roadmap (JSON File)' }}</h3>
                        <button @click="showImportModal = false" class="text-slate-400 hover:text-white">✕</button>
                    </div>

                    <form @submit.prevent="submitImport" class="space-y-3 text-xs">
                        <p class="text-slate-400">
                            {{ isArabic ? 'اختر ملف مسار تعليمي بتنسيق JSON المعياري. سيتم إنشاء معرفات جديدة للمسار للحفاظ على خصوصيتك واستقلالية تقدمك.' : 'Select a standard roadmap JSON file. Fresh UUIDs will be generated to ensure data privacy.' }}
                        </p>

                        <div>
                            <input
                                type="file"
                                accept=".json,.roadmap.json"
                                @change="(e: any) => importForm.json_file = e.target.files[0]"
                                class="w-full text-slate-400 file:bg-slate-800 file:border-0 file:rounded-lg file:text-xs file:py-2 file:px-3 file:text-slate-200"
                                required
                            />
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button
                                @click="showImportModal = false"
                                type="button"
                                class="px-4 py-2 rounded-xl text-slate-400 hover:text-white"
                            >
                                {{ isArabic ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button
                                type="submit"
                                :disabled="importForm.processing || !importForm.json_file"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2 rounded-xl"
                            >
                                {{ isArabic ? 'استيراد المسار' : 'Import' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
