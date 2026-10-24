<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Play, 
    Clock, 
    Calendar, 
    CheckCircle2, 
    AlertTriangle, 
    ArrowRight, 
    ArrowLeft,
    Sparkles, 
    Map, 
    ChevronRight,
    Repeat
} from 'lucide-vue-next';

interface EventItem {
    id: string;
    title: string;
    scheduled_start_utc: string;
    scheduled_end_utc: string;
    status: string;
    task?: {
        id: string;
        title: string;
        estimated_minutes: number;
        lesson?: {
            topic?: {
                phase?: {
                    roadmap?: {
                        title: string;
                    };
                };
            };
        };
    };
}

interface SrsItem {
    id: string;
    interval_days: number;
    topic: {
        id: string;
        title: string;
        phase?: {
            roadmap?: {
                title: string;
            };
        };
    };
}

interface RoadmapItem {
    id: string;
    title: string;
    status: string;
}

const props = defineProps<{
    todayEvents: EventItem[];
    upcomingEvent?: EventItem | null;
    minutesUntilNext?: number | null;
    plannedMinutesToday: number;
    actualMinutesToday: number;
    completedToday: number;
    pendingToday: number;
    overdueCount: number;
    activeSession?: any | null;
    assignedTasks: any[];
    dueSrsItems: SrsItem[];
    roadmaps: RoadmapItem[];
}>();

const page = usePage();
const isArabic = computed(() => page.props.locale === 'ar');

const startQuickSession = (eventId?: string, taskId?: string) => {
    router.post('/tracker/start', {
        event_id: eventId,
        task_id: taskId,
    });
};
</script>

<template>
    <AppLayout :title="isArabic ? 'اليوم' : 'Today Dashboard'">
        <div class="space-y-5">
            
            <!-- Prominent Active Session Banner if in Progress -->
            <div 
                v-if="activeSession"
                class="bg-gradient-to-r from-amber-600 to-indigo-700 rounded-2xl p-4.5 text-white shadow-xl flex items-center justify-between"
            >
                <div class="space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-white/20">
                        {{ isArabic ? 'جلسة مذاكرة نشطة الآن' : 'Active Session In Progress' }}
                    </span>
                    <h2 class="font-bold text-base line-clamp-1">
                        {{ activeSession.task ? activeSession.task.title : (activeSession.scheduledEvent?.title || 'Active Study Session') }}
                    </h2>
                    <p class="text-xs text-white/80">
                        {{ isArabic ? 'المؤقت قيد التشغيل - تتبع وقتك الحقيقي بدقة' : 'Timer is running — track your active focus duration' }}
                    </p>
                </div>
                <Link
                    href="/tracker"
                    class="bg-white text-slate-900 font-bold px-4 py-2 rounded-xl text-xs hover:bg-slate-100 transition shadow-lg shrink-0 flex items-center gap-1.5"
                >
                    <span>{{ isArabic ? 'فتح المؤقت' : 'Open Tracker' }}</span>
                    <component :is="isArabic ? ArrowLeft : ArrowRight" class="w-3.5 h-3.5" />
                </Link>
            </div>

            <!-- Hero Start Card (if no active session) -->
            <div 
                v-else
                class="bg-gradient-to-br from-indigo-900/80 via-slate-900 to-slate-900 border border-indigo-800/50 rounded-2xl p-5 shadow-xl relative overflow-hidden"
            >
                <div class="relative z-10 space-y-3">
                    <div class="flex items-center gap-2 text-indigo-400 text-xs font-semibold">
                        <Sparkles class="w-4 h-4 text-indigo-400 animate-pulse" />
                        <span>{{ isArabic ? 'التركيز اليومي' : 'Daily Study Focus' }}</span>
                    </div>

                    <div v-if="upcomingEvent">
                        <h2 class="text-lg font-bold text-white line-clamp-1">{{ upcomingEvent.title }}</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ isArabic ? `الجلسة القادمة تبدأ خلال ${minutesUntilNext} دقيقة` : `Next event starts in ${minutesUntilNext} minutes` }}
                        </p>
                    </div>
                    <div v-else>
                        <h2 class="text-lg font-bold text-white">
                            {{ isArabic ? 'جاهز لجلسة تعلم جديدة؟' : 'Ready for a focused study session?' }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ isArabic ? 'اختر مهمة من مساراتك وابدأ المؤقت لتسجيل وقتك وإنجازاتك.' : 'Pick a roadmap task and start your active timer.' }}
                        </p>
                    </div>

                    <div class="pt-2 flex items-center gap-3">
                        <button
                            @click="startQuickSession(upcomingEvent?.id, upcomingEvent?.task?.id)"
                            type="button"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-2 shadow-lg shadow-indigo-600/30 active:scale-95"
                        >
                            <Play class="w-4 h-4 fill-current" />
                            <span>{{ isArabic ? 'بدء جلسة مذاكرة الآن' : 'Start Study Session Now' }}</span>
                        </button>
                        <Link
                            href="/calendar"
                            class="text-xs text-slate-400 hover:text-slate-200 transition font-medium"
                        >
                            {{ isArabic ? 'عرض جدول التقويم' : 'View Calendar' }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Metrics Overview Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 space-y-1">
                    <span class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <Clock class="w-3.5 h-3.5 text-indigo-400" />
                        {{ isArabic ? 'وقت المذاكرة الفعلي' : 'Actual Study' }}
                    </span>
                    <div class="text-xl font-bold font-mono text-emerald-400">
                        {{ actualMinutesToday }} <span class="text-xs text-slate-400 font-sans">{{ isArabic ? 'دقيقة' : 'min' }}</span>
                    </div>
                    <span class="text-[10px] text-slate-500 block">
                        {{ isArabic ? `المخطط: ${plannedMinutesToday} د` : `Planned: ${plannedMinutesToday}m` }}
                    </span>
                </div>

                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 space-y-1">
                    <span class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400" />
                        {{ isArabic ? 'الجلسات المكتملة' : 'Completed' }}
                    </span>
                    <div class="text-xl font-bold font-mono text-white">
                        {{ completedToday }}
                    </div>
                    <span class="text-[10px] text-slate-500 block">
                        {{ isArabic ? `${pendingToday} متبقية اليوم` : `${pendingToday} remaining today` }}
                    </span>
                </div>

                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 space-y-1">
                    <span class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <AlertTriangle class="w-3.5 h-3.5 text-amber-400" />
                        {{ isArabic ? 'جلسات متأخرة' : 'Overdue' }}
                    </span>
                    <div class="text-xl font-bold font-mono" :class="overdueCount > 0 ? 'text-amber-400' : 'text-slate-400'">
                        {{ overdueCount }}
                    </div>
                    <span class="text-[10px] text-slate-500 block">
                        {{ isArabic ? 'تحتاج إعادة جدولة' : 'Needs attention' }}
                    </span>
                </div>

                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 space-y-1">
                    <span class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <Repeat class="w-3.5 h-3.5 text-sky-400" />
                        {{ isArabic ? 'مراجعات مستحقة' : 'SRS Reviews' }}
                    </span>
                    <div class="text-xl font-bold font-mono text-sky-400">
                        {{ dueSrsItems.length }}
                    </div>
                    <span class="text-[10px] text-slate-500 block">
                        {{ isArabic ? 'تكرار متباعد' : 'Spaced Repetition' }}
                    </span>
                </div>
            </div>

            <!-- Today's Scheduled Events -->
            <section class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4.5 space-y-3 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 font-bold text-sm text-white">
                        <Calendar class="w-4 h-4 text-indigo-400" />
                        <h2>{{ isArabic ? 'جدول جلسات اليوم' : "Today's Scheduled Sessions" }}</h2>
                    </div>
                    <Link href="/calendar" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">
                        {{ isArabic ? 'عرض الكل' : 'View all' }}
                    </Link>
                </div>

                <div v-if="todayEvents.length > 0" class="space-y-2">
                    <div
                        v-for="event in todayEvents"
                        :key="event.id"
                        class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3 flex items-center justify-between gap-3"
                    >
                        <div class="space-y-1 min-w-0">
                            <h3 class="font-medium text-xs text-slate-200 truncate">{{ event.title }}</h3>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                <span>{{ new Date(event.scheduled_start_utc).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                                <span>-</span>
                                <span>{{ new Date(event.scheduled_end_utc).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                                <span v-if="event.task?.lesson?.topic?.phase?.roadmap?.title" class="truncate text-indigo-400">
                                    • {{ event.task.lesson.topic.phase.roadmap.title }}
                                </span>
                            </div>
                        </div>

                        <button
                            v-if="event.status === 'scheduled'"
                            @click="startQuickSession(event.id, event.task?.id)"
                            type="button"
                            class="bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white p-2 rounded-lg transition active:scale-95"
                            :title="isArabic ? 'بدء الجلسة' : 'Start Session'"
                        >
                            <Play class="w-3.5 h-3.5 fill-current" />
                        </button>
                        <span 
                            v-else
                            class="text-[10px] px-2 py-0.5 rounded-md font-mono"
                            :class="event.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-400'"
                        >
                            {{ event.status }}
                        </span>
                    </div>
                </div>

                <div v-else class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-800 rounded-xl">
                    {{ isArabic ? 'لا توجد جلسات مجدولة لليوم. اختر مهمة أدناه وجدولها!' : 'No sessions scheduled for today. Schedule a task below!' }}
                </div>
            </section>

            <!-- Spaced Repetition Due Topics -->
            <section v-if="dueSrsItems.length > 0" class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4.5 space-y-3 shadow-md">
                <div class="flex items-center gap-2 font-bold text-sm text-sky-400">
                    <Repeat class="w-4 h-4" />
                    <h2>{{ isArabic ? 'مراجعات التكرار المتباعد المستحقة' : 'Spaced Repetition Reviews Due' }}</h2>
                </div>

                <div class="space-y-2">
                    <div 
                        v-for="srs in dueSrsItems"
                        :key="srs.id"
                        class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 flex items-center justify-between gap-3"
                    >
                        <div>
                            <h3 class="text-xs font-semibold text-slate-200">{{ srs.topic.title }}</h3>
                            <span class="text-[10px] text-slate-400">
                                {{ isArabic ? `الفترة: ${srs.interval_days} أيام` : `Interval: ${srs.interval_days} days` }}
                            </span>
                        </div>
                        <button
                            @click="startQuickSession(undefined, undefined)"
                            type="button"
                            class="text-xs bg-sky-600/20 text-sky-300 hover:bg-sky-600 hover:text-white px-3 py-1.5 rounded-lg transition"
                        >
                            {{ isArabic ? 'مراجعة الآن' : 'Review' }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Quick Access to Assigned Roadmap Tasks -->
            <section class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4.5 space-y-3 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 font-bold text-sm text-white">
                        <Map class="w-4 h-4 text-indigo-400" />
                        <h2>{{ isArabic ? 'مهام مسارات التعلم' : 'Roadmap Tasks' }}</h2>
                    </div>
                    <Link href="/roadmaps" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">
                        {{ isArabic ? 'المسارات' : 'View roadmaps' }}
                    </Link>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="task in assignedTasks"
                        :key="task.id"
                        class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <h3 class="text-xs font-medium text-slate-200 truncate">{{ task.title }}</h3>
                            <span class="text-[10px] text-slate-400">
                                {{ task.estimated_minutes }} {{ isArabic ? 'دقيقة' : 'min' }} • {{ task.task_type }}
                            </span>
                        </div>
                        <button
                            @click="startQuickSession(undefined, task.id)"
                            type="button"
                            class="text-xs bg-indigo-600/20 text-indigo-300 hover:bg-indigo-600 hover:text-white px-3 py-1.5 rounded-lg transition active:scale-95"
                        >
                            {{ isArabic ? 'مذاكرة' : 'Study' }}
                        </button>
                    </div>
                </div>
            </section>

        </div>
    </AppLayout>
</template>
