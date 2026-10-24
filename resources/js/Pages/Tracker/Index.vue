<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Play, 
    Pause, 
    CheckCircle2, 
    Clock, 
    Star, 
    Sparkles, 
    BookOpen, 
    ChevronRight,
    AlertCircle,
    RotateCcw
} from 'lucide-vue-next';

interface ActiveSession {
    id: string;
    planned_start_utc: string;
    planned_end_utc: string;
    actual_start_utc: string | null;
    total_active_seconds: number;
    status: 'in_progress' | 'paused';
    task?: {
        id: string;
        title: string;
        task_type: string;
    };
    scheduledEvent?: {
        id: string;
        title: string;
    };
    intervals: Array<{
        id: string;
        interval_type: string;
        started_at_utc: string;
        ended_at_utc: string | null;
        duration_seconds: number;
    }>;
}

interface ReadyEvent {
    id: string;
    title: string;
    scheduled_start_utc: string;
    task?: {
        id: string;
        title: string;
    };
}

interface RecentSession {
    id: string;
    actual_start_utc: string;
    total_active_seconds: number;
    self_rating: number | null;
    status: string;
    accomplishments: string | null;
    task?: {
        title: string;
    };
}

const props = defineProps<{
    activeSession?: ActiveSession | null;
    readyEvents: ReadyEvent[];
    recentSessions: RecentSession[];
}>();

const page = usePage();
const isArabic = computed(() => page.props.locale === 'ar');

// Live seconds ticker
const timerSeconds = ref(0);
let intervalHandle: any = null;

const computeCurrentActiveSeconds = () => {
    if (!props.activeSession) return 0;
    let total = 0;
    const now = Date.now();

    props.activeSession.intervals.forEach(iv => {
        if (iv.interval_type === 'active') {
            if (iv.ended_at_utc) {
                total += iv.duration_seconds;
            } else {
                const start = new Date(iv.started_at_utc).getTime();
                total += Math.max(0, Math.floor((now - start) / 1000));
            }
        }
    });
    return total;
};

onMounted(() => {
    timerSeconds.value = computeCurrentActiveSeconds();
    intervalHandle = setInterval(() => {
        if (props.activeSession && props.activeSession.status === 'in_progress') {
            timerSeconds.value = computeCurrentActiveSeconds();
        }
    }, 1000);
});

onUnmounted(() => {
    if (intervalHandle) clearInterval(intervalHandle);
});

const formattedTime = computed(() => {
    const s = timerSeconds.value;
    const hrs = Math.floor(s / 3600);
    const mins = Math.floor((s % 3600) / 60);
    const secs = s % 60;
    return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const pause = () => {
    if (!props.activeSession) return;
    router.post(`/tracker/${props.activeSession.id}/pause`, {}, { preserveScroll: true });
};

const resume = () => {
    if (!props.activeSession) return;
    router.post(`/tracker/${props.activeSession.id}/resume`, {}, { preserveScroll: true });
};

// Review Modal Form
const showReviewModal = ref(false);
const reviewForm = useForm({
    self_rating: 8,
    status: 'completed',
    accomplishments: '',
    obstacles: '',
    notes: '',
});

const promptFinish = () => {
    showReviewModal.value = true;
};

const submitReview = () => {
    if (!props.activeSession) return;
    reviewForm.post(`/tracker/${props.activeSession.id}/finish`, {
        preserveScroll: true,
        onSuccess: () => {
            showReviewModal.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :title="isArabic ? 'مؤقت جلسات المذاكرة' : 'Study Session Tracker'">
        <div class="space-y-5">
            
            <!-- Active Timer Card -->
            <div 
                v-if="activeSession"
                class="bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-900 border border-indigo-700/60 rounded-3xl p-6 sm:p-8 text-center space-y-6 shadow-2xl relative overflow-hidden"
            >
                <!-- Status Badge -->
                <div class="flex items-center justify-center gap-2">
                    <span 
                        class="text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border flex items-center gap-1.5"
                        :class="activeSession.status === 'in_progress' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="activeSession.status === 'in_progress' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
                        {{ activeSession.status === 'in_progress' ? (isArabic ? 'جلسة نشطة قيد التسجيل' : 'Focus Timer Active') : (isArabic ? 'متوقف مؤقتاً' : 'Paused') }}
                    </span>
                </div>

                <!-- Session / Task Title -->
                <div class="space-y-1">
                    <h1 class="text-xl sm:text-2xl font-bold text-white leading-tight">
                        {{ activeSession.task ? activeSession.task.title : (activeSession.scheduledEvent?.title || 'Focused Study Session') }}
                    </h1>
                    <p class="text-xs text-indigo-300">
                        {{ isArabic ? 'يتم احتساب الوقت النشط الفعلي واستبعاد فترات التوقف' : 'Excludes pause intervals from active duration math' }}
                    </p>
                </div>

                <!-- Big Clock Display -->
                <div class="py-2">
                    <span class="text-5xl sm:text-6xl font-mono font-bold text-white tracking-widest drop-shadow-lg">
                        {{ formattedTime }}
                    </span>
                </div>

                <!-- Control Buttons -->
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button
                        v-if="activeSession.status === 'in_progress'"
                        @click="pause"
                        type="button"
                        class="bg-slate-800 hover:bg-slate-700 text-amber-400 font-semibold px-5 py-3 rounded-2xl text-xs flex items-center gap-2 transition active:scale-95 shadow-md"
                    >
                        <Pause class="w-4 h-4 fill-current" />
                        <span>{{ isArabic ? 'إيقاف مؤقت' : 'Pause' }}</span>
                    </button>
                    <button
                        v-else
                        @click="resume"
                        type="button"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-5 py-3 rounded-2xl text-xs flex items-center gap-2 transition active:scale-95 shadow-md shadow-indigo-600/30"
                    >
                        <Play class="w-4 h-4 fill-current" />
                        <span>{{ isArabic ? 'استئناف' : 'Resume' }}</span>
                    </button>

                    <button
                        @click="promptFinish"
                        type="button"
                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-6 py-3 rounded-2xl text-xs flex items-center gap-2 transition active:scale-95 shadow-lg shadow-emerald-600/30"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>{{ isArabic ? 'إنهاء وتقييم الجلسة' : 'Finish & Review' }}</span>
                    </button>
                </div>
            </div>

            <!-- Empty / Ready to Start Sessions -->
            <div 
                v-else
                class="bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center space-y-4 shadow-xl"
            >
                <div class="w-16 h-16 rounded-2xl bg-indigo-600/10 text-indigo-400 flex items-center justify-center mx-auto border border-indigo-500/20">
                    <Clock class="w-8 h-8" />
                </div>
                <div class="space-y-1">
                    <h2 class="text-base font-bold text-white">{{ isArabic ? 'لا توجد جلسة مذاكرة نشطة حالياً' : 'No Active Session Right Now' }}</h2>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        {{ isArabic ? 'اختر جلسة مجدولة أدناه أو ابدأ جلسة فورية للتركيز وتسجيل إنجازك.' : 'Select a scheduled event below or start a new focus session.' }}
                    </p>
                </div>

                <!-- Ready Events List -->
                <div v-if="readyEvents.length > 0" class="pt-3 max-w-md mx-auto space-y-2 text-start">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">
                        {{ isArabic ? 'جلسات جاهزة للبدء اليوم:' : 'Upcoming Scheduled Sessions:' }}
                    </span>
                    <div
                        v-for="ev in readyEvents"
                        :key="ev.id"
                        class="bg-slate-950 p-3 rounded-xl border border-slate-800 flex items-center justify-between gap-3 text-xs"
                    >
                        <span class="font-medium text-slate-200 truncate">{{ ev.title }}</span>
                        <button
                            @click="router.post('/tracker/start', { event_id: ev.id })"
                            type="button"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-3 py-1.5 rounded-lg transition shrink-0 flex items-center gap-1"
                        >
                            <Play class="w-3 h-3 fill-current" />
                            <span>{{ isArabic ? 'بدء' : 'Start' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Completed Sessions History -->
            <section class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 space-y-3.5 shadow-md">
                <div class="flex items-center gap-2 font-bold text-sm text-white">
                    <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                    <h2>{{ isArabic ? 'سجل الجلسات السابقة والمراجعات' : 'Session History & Reviews' }}</h2>
                </div>

                <div v-if="recentSessions.length > 0" class="space-y-2.5">
                    <div
                        v-for="s in recentSessions"
                        :key="s.id"
                        class="bg-slate-950/80 border border-slate-800 rounded-xl p-3.5 space-y-2 text-xs"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-slate-200">{{ s.task?.title || 'Focused Study Session' }}</h3>
                                <span class="text-[10px] text-slate-400 font-mono">
                                    {{ new Date(s.actual_start_utc).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                    • {{ Math.round(s.total_active_seconds / 60) }} {{ isArabic ? 'دقيقة نشطة' : 'active mins' }}
                                </span>
                            </div>
                            <div v-if="s.self_rating" class="flex items-center gap-1 bg-amber-400/10 text-amber-400 border border-amber-400/20 px-2 py-0.5 rounded-md font-mono text-[11px]">
                                <Star class="w-3 h-3 fill-current" />
                                <span>{{ s.self_rating }}/10</span>
                            </div>
                        </div>

                        <p v-if="s.accomplishments" class="text-slate-300 text-[11px] bg-slate-900/60 p-2 rounded-lg border border-slate-800/60">
                            <span class="text-indigo-400 font-semibold">{{ isArabic ? 'الإنجاز:' : 'Accomplishments:' }}</span> {{ s.accomplishments }}
                        </p>
                    </div>
                </div>

                <div v-else class="text-center py-6 text-slate-500 text-xs border border-dashed border-slate-800 rounded-xl">
                    {{ isArabic ? 'لا توجد جلسات مكتملة حتى الآن. ابدأ جلستك الأولى!' : 'No completed study sessions yet.' }}
                </div>
            </section>

            <!-- Qualitative Review Modal -->
            <div v-if="showReviewModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-4 text-xs">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                        <h3 class="font-bold text-sm text-white flex items-center gap-1.5">
                            <Sparkles class="w-4 h-4 text-amber-400" />
                            <span>{{ isArabic ? 'مراجعة وتقييم الجلسة' : 'Post-Session Qualitative Review' }}</span>
                        </h3>
                        <button @click="showReviewModal = false" class="text-slate-400 hover:text-white">✕</button>
                    </div>

                    <form @submit.prevent="submitReview" class="space-y-3.5">
                        <!-- Rating 1-10 -->
                        <div>
                            <label class="block text-slate-300 font-medium mb-1.5">
                                {{ isArabic ? `التقييم الذاتي للتركيز والفهم: (${reviewForm.self_rating} / 10)` : `Self-Rating (1-10): ${reviewForm.self_rating}` }}
                            </label>
                            <input
                                v-model.number="reviewForm.self_rating"
                                type="range"
                                min="1"
                                max="10"
                                class="w-full accent-indigo-500"
                            />
                            <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                                <span>1 (Struggled)</span>
                                <span>5 (Moderate)</span>
                                <span>10 (Mastered)</span>
                            </div>
                        </div>

                        <!-- Completion Status -->
                        <div>
                            <label class="block text-slate-300 font-medium mb-1">{{ isArabic ? 'حالة الإنجاز' : 'Completion Status' }}</label>
                            <select v-model="reviewForm.status" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100">
                                <option value="completed">{{ isArabic ? 'مكتمل بنجاح (Completed)' : 'Completed Fully' }}</option>
                                <option value="partially_completed">{{ isArabic ? 'مكتمل جزئياً (Partially Completed)' : 'Partially Completed' }}</option>
                            </select>
                        </div>

                        <!-- Accomplishments -->
                        <div>
                            <label class="block text-slate-300 font-medium mb-1">{{ isArabic ? 'ما الذي أنجزته في هذه الجلسة؟' : 'Accomplishments' }}</label>
                            <textarea
                                v-model="reviewForm.accomplishments"
                                rows="2"
                                :placeholder="isArabic ? 'أهم المفاهيم أو المهام المنجزة...' : 'What did you accomplish?'"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-100"
                            ></textarea>
                        </div>

                        <!-- Obstacles -->
                        <div>
                            <label class="block text-slate-300 font-medium mb-1">{{ isArabic ? 'صعوبات أو نقاط تحتاج مراجعة' : 'Obstacles or Notes' }}</label>
                            <textarea
                                v-model="reviewForm.obstacles"
                                rows="2"
                                :placeholder="isArabic ? 'أي نقاط استغرقت وقتاً إضافياً...' : 'Any roadblocks or topics needing review?'"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-100"
                            ></textarea>
                        </div>

                        <div class="pt-2 flex justify-end gap-2">
                            <button @click="showReviewModal = false" type="button" class="px-4 py-2 text-slate-400">Cancel</button>
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-5 py-2 rounded-xl shadow-lg shadow-emerald-600/30">
                                {{ isArabic ? 'حفظ المراجعة وإنهاء الجلسة' : 'Submit Review & Complete' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
