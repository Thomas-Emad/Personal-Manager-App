<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Calendar as CalendarIcon, 
    Clock, 
    Plus, 
    Trash2, 
    AlertTriangle, 
    ChevronLeft, 
    ChevronRight,
    Sliders,
    Moon,
    Play
} from 'lucide-vue-next';

interface EventItem {
    id: string;
    title: string;
    scheduled_start_utc: string;
    scheduled_end_utc: string;
    reminder_lead_minutes: number;
    status: string;
    task?: {
        id: string;
        title: string;
    };
}

interface AvailabilityRule {
    id: string;
    day_of_week: number;
    start_time: string;
    end_time: string;
    is_rest_day: boolean;
}

interface SchedulableTask {
    id: string;
    title: string;
    estimated_minutes: number;
    task_type: string;
}

const props = defineProps<{
    view: 'day' | 'week' | 'month';
    selectedDate: string;
    rangeStart: string;
    rangeEnd: string;
    events: EventItem[];
    availabilityRules: AvailabilityRule[];
    schedulableTasks: SchedulableTask[];
}>();

const page = usePage();
const isArabic = computed(() => page.props.locale === 'ar');

const showCreateModal = ref(false);
const showAvailabilityModal = ref(false);

// Event Create Form
const eventForm = useForm({
    title: '',
    task_id: '',
    scheduled_start_utc: `${props.selectedDate}T09:00`,
    scheduled_end_utc: `${props.selectedDate}T10:00`,
    reminder_lead_minutes: 15,
});

const onTaskSelected = (e: Event) => {
    const target = e.target as HTMLSelectElement;
    const task = props.schedulableTasks.find(t => t.id === target.value);
    if (task) {
        eventForm.title = task.title;
    }
};

const submitEvent = () => {
    eventForm.post('/calendar/events', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            eventForm.reset();
        },
    });
};

const deleteEvent = (id: string) => {
    if (confirm(isArabic.value ? 'هل أنت متأكد من إلغاء هذا الموعد؟' : 'Cancel this scheduled event?')) {
        router.delete(`/calendar/events/${id}`, { preserveScroll: true });
    }
};

const changeView = (newView: 'day' | 'week' | 'month') => {
    router.get('/calendar', {
        view: newView,
        date: props.selectedDate,
    }, { preserveState: true });
};

const dayNames = computed(() => {
    return isArabic.value 
        ? ['الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد']
        : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
});
</script>

<template>
    <AppLayout :title="isArabic ? 'التقويم والجدولة' : 'Calendar & Schedule'">
        <div class="space-y-4">
            
            <!-- Top Controls Bar -->
            <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md">
                <!-- View Switcher -->
                <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800 text-xs font-semibold">
                    <button
                        @click="changeView('day')"
                        type="button"
                        :class="view === 'day' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                        class="px-3 py-1 rounded-lg transition"
                    >
                        {{ isArabic ? 'يومي' : 'Day' }}
                    </button>
                    <button
                        @click="changeView('week')"
                        type="button"
                        :class="view === 'week' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                        class="px-3 py-1 rounded-lg transition"
                    >
                        {{ isArabic ? 'أسبوعي' : 'Week' }}
                    </button>
                    <button
                        @click="changeView('month')"
                        type="button"
                        :class="view === 'month' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                        class="px-3 py-1 rounded-lg transition"
                    >
                        {{ isArabic ? 'شهري' : 'Month' }}
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Availability Preferences -->
                    <button
                        @click="showAvailabilityModal = !showAvailabilityModal"
                        type="button"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                        :title="isArabic ? 'إعدادات أوقات الفراغ وأيام الراحة' : 'Availability & Rest Days'"
                    >
                        <Sliders class="w-4 h-4 text-indigo-400" />
                    </button>

                    <!-- Schedule Event Button -->
                    <button
                        @click="showCreateModal = true"
                        type="button"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition shadow-md shadow-indigo-600/30"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ isArabic ? 'موعد جديد' : 'New Event' }}</span>
                    </button>
                </div>
            </div>

            <!-- Availability & Rest Days Panel -->
            <div v-if="showAvailabilityModal" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-xs font-bold text-white flex items-center gap-1.5">
                        <Moon class="w-3.5 h-3.5 text-indigo-400" />
                        <span>{{ isArabic ? 'أوقات المذاكرة وأيام الراحة الأسبوعية' : 'Weekly Availability & Rest Days' }}</span>
                    </h3>
                    <button @click="showAvailabilityModal = false" class="text-slate-500 hover:text-slate-300 text-xs">✕</button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                    <div 
                        v-for="rule in availabilityRules" 
                        :key="rule.id"
                        class="bg-slate-950 p-2.5 rounded-xl border border-slate-800"
                    >
                        <div class="font-semibold text-slate-200 mb-1">
                            {{ dayNames[rule.day_of_week - 1] }}
                        </div>
                        <span 
                            v-if="rule.is_rest_day" 
                            class="text-[10px] text-amber-400 bg-amber-400/10 px-1.5 py-0.5 rounded font-mono"
                        >
                            {{ isArabic ? 'يوم راحة' : 'Rest Day' }}
                        </span>
                        <span v-else class="text-[10px] text-slate-400 font-mono">
                            {{ rule.start_time }} - {{ rule.end_time }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Events List View -->
            <div class="space-y-2.5">
                <div
                    v-for="event in events"
                    :key="event.id"
                    class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 flex items-center justify-between gap-4 shadow-sm"
                >
                    <div class="space-y-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span 
                                class="w-2 h-2 rounded-full" 
                                :class="event.status === 'completed' ? 'bg-emerald-400' : (event.status === 'in_progress' ? 'bg-amber-400 animate-ping' : 'bg-indigo-400')"
                            ></span>
                            <h3 class="font-semibold text-sm text-slate-100 truncate">{{ event.title }}</h3>
                        </div>

                        <div class="flex items-center gap-3 text-xs text-slate-400 font-mono">
                            <span class="flex items-center gap-1">
                                <Clock class="w-3 h-3 text-indigo-400" />
                                {{ new Date(event.scheduled_start_utc).toLocaleDateString([], { month: 'short', day: 'numeric' }) }}
                                {{ new Date(event.scheduled_start_utc).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                -
                                {{ new Date(event.scheduled_end_utc).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                            </span>
                            <span v-if="event.reminder_lead_minutes > 0" class="text-[10px] text-indigo-300">
                                🔔 {{ event.reminder_lead_minutes }}m {{ isArabic ? 'قبل' : 'prior' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            v-if="event.status === 'scheduled'"
                            @click="router.post('/tracker/start', { event_id: event.id })"
                            type="button"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white p-2 rounded-xl transition active:scale-95 shadow-md shadow-indigo-600/20"
                            :title="isArabic ? 'بدء الجلسة الآن' : 'Start Session'"
                        >
                            <Play class="w-4 h-4 fill-current" />
                        </button>
                        <button
                            @click="deleteEvent(event.id)"
                            type="button"
                            class="text-slate-500 hover:text-rose-400 p-2 rounded-xl hover:bg-rose-500/10 transition"
                            :title="isArabic ? 'إلغاء' : 'Cancel'"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div v-if="events.length === 0" class="text-center py-12 text-slate-500 text-xs border border-dashed border-slate-800 rounded-2xl bg-slate-900/40">
                    {{ isArabic ? 'لا توجد مواعيد مجدولة في هذا النطاق الزمني.' : 'No scheduled sessions in this range. Create one above!' }}
                </div>
            </div>

            <!-- Create Event Modal -->
            <div 
                v-if="showCreateModal" 
                class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
            >
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="font-bold text-sm text-white">{{ isArabic ? 'جدولة جلسة دراسية جديدة' : 'Schedule New Study Event' }}</h3>
                        <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">✕</button>
                    </div>

                    <form @submit.prevent="submitEvent" class="space-y-3 text-xs">
                        <!-- Link to Task -->
                        <div>
                            <label class="block text-slate-400 mb-1">{{ isArabic ? 'ربط بمهمة من المسار (اختياري)' : 'Link to Roadmap Task (Optional)' }}</label>
                            <select
                                v-model="eventForm.task_id"
                                @change="onTaskSelected"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-200"
                            >
                                <option value="">-- {{ isArabic ? 'جلسة حرة / بدون مهمة' : 'Free Session / No Task' }} --</option>
                                <option v-for="t in schedulableTasks" :key="t.id" :value="t.id">
                                    {{ t.title }} ({{ t.estimated_minutes }}m)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1">{{ isArabic ? 'عنوان الجلسة' : 'Session Title' }}</label>
                            <input
                                v-model="eventForm.title"
                                type="text"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-slate-400 mb-1">{{ isArabic ? 'وقت البدء (UTC)' : 'Start Time (UTC)' }}</label>
                                <input
                                    v-model="eventForm.scheduled_start_utc"
                                    type="datetime-local"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-100 font-mono text-[11px]"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">{{ isArabic ? 'وقت الانتهاء (UTC)' : 'End Time (UTC)' }}</label>
                                <input
                                    v-model="eventForm.scheduled_end_utc"
                                    type="datetime-local"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-100 font-mono text-[11px]"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1">{{ isArabic ? 'تذكير مسبق (دقائق)' : 'Notification Reminder Lead (Minutes)' }}</label>
                            <input
                                v-model.number="eventForm.reminder_lead_minutes"
                                type="number"
                                min="0"
                                max="120"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-100"
                            />
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
                                :disabled="eventForm.processing"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2 rounded-xl shadow-lg shadow-indigo-600/30"
                            >
                                {{ isArabic ? 'جدولة الموعد' : 'Schedule Event' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
