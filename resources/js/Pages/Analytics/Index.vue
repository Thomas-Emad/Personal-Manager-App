<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BarChart3, 
    Clock, 
    CheckCircle2, 
    Star, 
    Flame, 
    Calendar, 
    BrainCircuit, 
    ArrowRight, 
    ArrowLeft,
    TrendingUp,
    Map
} from 'lucide-vue-next';

interface RoadmapProgress {
    id: string;
    title: string;
    progress: {
        total_tasks: number;
        completed_tasks: number;
        completion_rate: number;
        estimated_minutes_total: number;
        estimated_minutes_completed: number;
    };
}

interface DayStat {
    date: string;
    day: string;
    minutes: number;
}

interface SrsItem {
    id: string;
    topic_id: string;
    repetition_number: number;
    interval_days: number;
    ease_factor: number;
    next_review_at_utc: string;
    status: string;
    topic?: {
        id: string;
        title: string;
        phase?: {
            title: string;
            roadmap?: {
                id: string;
                title: string;
            };
        };
    };
}

const props = defineProps<{
    roadmaps: RoadmapProgress[];
    totalHours: number;
    sessionsCount: number;
    averageRating: number;
    last7Days: DayStat[];
    topicsNeedingReview: SrsItem[];
    globalCompletionRate: number;
    totalTasks: number;
    completedTasks: number;
}>();

const page = usePage();
const isArabic = computed(() => page.props.locale === 'ar');

// Find max minutes in last 7 days for bar scaling
const maxMinutes = computed(() => {
    const max = Math.max(...props.last7Days.map(d => d.minutes), 1);
    return Math.max(max, 60); // min scale 60 mins
});

const formatDate = (dateStr: string) => {
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString(isArabic.value ? 'ar-EG' : 'en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <AppLayout :title="isArabic ? 'التحليلات والتقدم' : 'Progress & Analytics'">
        <div class="space-y-6">
            
            <!-- Page Header -->
            <div>
                <h1 class="text-xl font-bold text-white flex items-center gap-2">
                    <BarChart3 class="w-6 h-6 text-indigo-400" />
                    <span>{{ isArabic ? 'التحليلات وقياس التقدم' : 'Progress & Analytics' }}</span>
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ isArabic ? 'مؤشرات الأداء، الساعات الفعلية، ومراجعات التكرار المتباعد (Spaced Repetition)' : 'Performance metrics, actual study duration, and spaced repetition review schedules' }}
                </p>
            </div>

            <!-- Top Key Metrics Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <!-- Total Study Time -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 space-y-1">
                    <div class="flex items-center justify-between text-slate-400 text-xs">
                        <span>{{ isArabic ? 'ساعات المذاكرة' : 'Study Time' }}</span>
                        <Clock class="w-4 h-4 text-indigo-400" />
                    </div>
                    <div class="text-xl font-black text-white">
                        {{ totalHours }} <span class="text-xs font-normal text-slate-400">{{ isArabic ? 'ساعة' : 'hrs' }}</span>
                    </div>
                    <div class="text-[10px] text-indigo-300">
                        {{ isArabic ? 'وقت صافي فعلي' : 'Active focus time' }}
                    </div>
                </div>

                <!-- Completed Sessions -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 space-y-1">
                    <div class="flex items-center justify-between text-slate-400 text-xs">
                        <span>{{ isArabic ? 'الجلسات المكتملة' : 'Sessions' }}</span>
                        <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                    </div>
                    <div class="text-xl font-black text-white">
                        {{ sessionsCount }}
                    </div>
                    <div class="text-[10px] text-emerald-300">
                        {{ isArabic ? 'جلسة مدروسة' : 'Completed sessions' }}
                    </div>
                </div>

                <!-- Average Rating -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 space-y-1">
                    <div class="flex items-center justify-between text-slate-400 text-xs">
                        <span>{{ isArabic ? 'متوسط التقييم' : 'Avg Rating' }}</span>
                        <Star class="w-4 h-4 text-amber-400 fill-amber-400/20" />
                    </div>
                    <div class="text-xl font-black text-white">
                        {{ averageRating }} <span class="text-xs font-normal text-slate-400">/ 10</span>
                    </div>
                    <div class="text-[10px] text-amber-300">
                        {{ isArabic ? 'تقييم الاستيعاب' : 'Quality self-rating' }}
                    </div>
                </div>

                <!-- Global Completion Rate -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 space-y-1">
                    <div class="flex items-center justify-between text-slate-400 text-xs">
                        <span>{{ isArabic ? 'إنجاز المهام' : 'Tasks Done' }}</span>
                        <TrendingUp class="w-4 h-4 text-sky-400" />
                    </div>
                    <div class="text-xl font-black text-white">
                        {{ globalCompletionRate }}%
                    </div>
                    <div class="text-[10px] text-sky-300">
                        {{ completedTasks }} / {{ totalTasks }} {{ isArabic ? 'مهمة' : 'tasks' }}
                    </div>
                </div>
            </div>

            <!-- Last 7 Days Activity Bar Chart -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-white flex items-center gap-2">
                            <Flame class="w-4 h-4 text-amber-400" />
                            <span>{{ isArabic ? 'نشاط المذاكرة اليومي (آخر 7 أيام)' : 'Daily Focus Duration (Last 7 Days)' }}</span>
                        </h2>
                        <span class="text-[11px] text-slate-400">
                            {{ isArabic ? 'الدقائق الفعلية التي قضيتها في جلسات المذاكرة' : 'Total active minutes spent per day' }}
                        </span>
                    </div>
                </div>

                <!-- CSS Bars -->
                <div class="h-44 flex items-end justify-between gap-2 pt-6 pb-2 px-2 border-b border-slate-800">
                    <div
                        v-for="day in last7Days"
                        :key="day.date"
                        class="flex-1 flex flex-col items-center gap-2 h-full justify-end group"
                    >
                        <!-- Tooltip / Value on top -->
                        <span class="text-[10px] font-mono text-indigo-300 opacity-80 group-hover:opacity-100 transition">
                            {{ day.minutes > 0 ? day.minutes + 'm' : '-' }}
                        </span>

                        <!-- Bar -->
                        <div class="w-full max-w-[28px] bg-slate-800/80 rounded-t-lg overflow-hidden flex items-end h-full">
                            <div
                                :style="{ height: `${Math.round((day.minutes / maxMinutes) * 100)}%` }"
                                :class="day.minutes > 0 ? 'bg-gradient-to-t from-indigo-600 to-indigo-400 group-hover:from-indigo-500 group-hover:to-indigo-300' : 'bg-transparent'"
                                class="w-full rounded-t-lg transition-all duration-500"
                            ></div>
                        </div>

                        <!-- Day Label -->
                        <div class="text-center">
                            <span class="block text-[11px] font-semibold text-slate-300">{{ day.day }}</span>
                            <span class="block text-[9px] text-slate-500">{{ day.date.split(' ')[1] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Roadmaps Progress Breakdown -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white flex items-center gap-2">
                        <Map class="w-4 h-4 text-indigo-400" />
                        <span>{{ isArabic ? 'تقدم المسارات التعليمية' : 'Roadmap Progress Breakdown' }}</span>
                    </h2>
                    <Link href="/roadmaps" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">
                        <span>{{ isArabic ? 'عرض الكل' : 'View all' }}</span>
                        <component :is="isArabic ? ArrowLeft : ArrowRight" class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div v-if="roadmaps.length > 0" class="space-y-3">
                    <div
                        v-for="rm in roadmaps"
                        :key="rm.id"
                        class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3.5 space-y-2.5"
                    >
                        <div class="flex items-center justify-between">
                            <Link :href="`/roadmaps/${rm.id}`" class="text-xs font-bold text-white hover:text-indigo-400 transition">
                                {{ rm.title }}
                            </Link>
                            <span class="text-xs font-bold font-mono text-indigo-400">
                                {{ rm.progress.completion_rate }}%
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div
                                :style="{ width: `${rm.progress.completion_rate}%` }"
                                class="bg-indigo-500 h-2 rounded-full transition-all duration-500"
                            ></div>
                        </div>

                        <div class="flex items-center justify-between text-[10px] text-slate-400">
                            <span>
                                {{ rm.progress.completed_tasks }} / {{ rm.progress.total_tasks }} {{ isArabic ? 'مهام منجزة' : 'tasks completed' }}
                            </span>
                            <span>
                                {{ Math.round(rm.progress.estimated_minutes_completed / 60) }} / {{ Math.round(rm.progress.estimated_minutes_total / 60) }} {{ isArabic ? 'ساعة مقدرة' : 'hrs estimated' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-6 text-xs text-slate-500">
                    {{ isArabic ? 'لا توجد مسارات مضافة بعد' : 'No roadmaps created yet' }}
                </div>
            </div>

            <!-- Spaced Repetition (Module I) & Topics Due for Review -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-white flex items-center gap-2">
                            <BrainCircuit class="w-4 h-4 text-emerald-400" />
                            <span>{{ isArabic ? 'جدول التكرار المتباعد (Spaced Repetition)' : 'Spaced Repetition (SM-2 Reviews)' }}</span>
                        </h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            {{ isArabic ? 'مراجعات مجدولة تلقائياً وفق خوارزمية SM-2 بناءً على تقييماتك' : 'Review intervals automatically calculated via SM-2 based on session retention ratings' }}
                        </p>
                    </div>
                </div>

                <div v-if="topicsNeedingReview.length > 0" class="space-y-2.5">
                    <div
                        v-for="srs in topicsNeedingReview"
                        :key="srs.id"
                        class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3 flex items-center justify-between gap-3"
                    >
                        <div class="space-y-1">
                            <div class="text-xs font-semibold text-white">
                                {{ srs.topic?.title || 'Study Topic' }}
                            </div>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                <span v-if="srs.topic?.phase?.roadmap" class="text-indigo-400">
                                    {{ srs.topic.phase.roadmap.title }}
                                </span>
                                <span>•</span>
                                <span>{{ isArabic ? 'التكرار رقم' : 'Repetition' }}: {{ srs.repetition_number }}</span>
                                <span>•</span>
                                <span>{{ isArabic ? 'الفاصل' : 'Interval' }}: {{ srs.interval_days }} {{ isArabic ? 'يوم' : 'days' }}</span>
                            </div>
                        </div>

                        <div class="text-end shrink-0">
                            <div class="text-[10px] text-slate-400">
                                {{ isArabic ? 'المراجعة القادمة' : 'Next Review' }}
                            </div>
                            <div class="text-xs font-mono font-medium text-emerald-400">
                                {{ formatDate(srs.next_review_at_utc) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-6 text-xs text-slate-500">
                    {{ isArabic ? 'لا توجد مواضيع بانتظار المراجعة حالياً. رائع!' : 'No topics due for spaced review right now. All caught up!' }}
                </div>
            </div>

        </div>
    </AppLayout>
</template>
