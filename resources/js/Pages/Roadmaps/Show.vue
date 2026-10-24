<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Map, 
    Plus, 
    ChevronDown, 
    ChevronUp, 
    CheckCircle2, 
    Clock, 
    Calendar, 
    Link as LinkIcon, 
    BookOpen, 
    Trash2, 
    Play,
    AlertCircle,
    ArrowLeft,
    ArrowRight
} from 'lucide-vue-next';

interface TaskItem {
    id: string;
    title: string;
    description: string | null;
    task_type: string;
    estimated_minutes: number;
    sort_order: number;
    status: string;
    prerequisites?: TaskItem[];
}

interface LessonItem {
    id: string;
    title: string;
    sort_order: number;
    tasks: TaskItem[];
}

interface TopicItem {
    id: string;
    title: string;
    estimated_minutes: number;
    sort_order: number;
    lessons: LessonItem[];
}

interface PhaseItem {
    id: string;
    title: string;
    description: string | null;
    sort_order: number;
    topics: TopicItem[];
}

interface RoadmapDetail {
    id: string;
    title: string;
    description: string | null;
    status: string;
    phases: PhaseItem[];
}

const props = defineProps<{
    roadmap: RoadmapDetail;
    progress: {
        total_tasks: number;
        completed_tasks: number;
        completion_rate: number;
        estimated_minutes_total: number;
        estimated_minutes_completed: number;
    };
}>();

const page = usePage();
const isArabic = computed(() => page.props.locale === 'ar');

// Modals
const showAddPhaseModal = ref(false);
const activePhaseId = ref<string | null>(null);
const showAddTopicModal = ref(false);
const activeTopicId = ref<string | null>(null);
const showAddLessonModal = ref(false);
const activeLessonId = ref<string | null>(null);
const showAddTaskModal = ref(false);

const showScheduleModal = ref(false);
const activeScheduleTask = ref<TaskItem | null>(null);

// Forms
const phaseForm = useForm({ title: '', description: '' });
const topicForm = useForm({ title: '', estimated_minutes: 60 });
const lessonForm = useForm({ title: '', content: '' });
const taskForm = useForm({
    title: '',
    description: '',
    task_type: 'reading',
    estimated_minutes: 30,
});

const scheduleForm = useForm({
    start_utc: new Date(Date.now() + 3600000).toISOString().slice(0, 16),
    duration_minutes: 45,
});

const submitPhase = () => {
    phaseForm.post(`/roadmaps/${props.roadmap.id}/phases`, {
        preserveScroll: true,
        onSuccess: () => {
            showAddPhaseModal.value = false;
            phaseForm.reset();
        },
    });
};

const openAddTopic = (phaseId: string) => {
    activePhaseId.value = phaseId;
    showAddTopicModal.value = true;
};

const submitTopic = () => {
    if (!activePhaseId.value) return;
    topicForm.post(`/phases/${activePhaseId.value}/topics`, {
        preserveScroll: true,
        onSuccess: () => {
            showAddTopicModal.value = false;
            topicForm.reset();
        },
    });
};

const openAddLesson = (topicId: string) => {
    activeTopicId.value = topicId;
    showAddLessonModal.value = true;
};

const submitLesson = () => {
    if (!activeTopicId.value) return;
    lessonForm.post(`/topics/${activeTopicId.value}/lessons`, {
        preserveScroll: true,
        onSuccess: () => {
            showAddLessonModal.value = false;
            lessonForm.reset();
        },
    });
};

const openAddTask = (lessonId: string) => {
    activeLessonId.value = lessonId;
    showAddTaskModal.value = true;
};

const submitTask = () => {
    if (!activeLessonId.value) return;
    taskForm.post(`/lessons/${activeLessonId.value}/tasks`, {
        preserveScroll: true,
        onSuccess: () => {
            showAddTaskModal.value = false;
            taskForm.reset();
        },
    });
};

const openSchedule = (task: TaskItem) => {
    activeScheduleTask.value = task;
    scheduleForm.duration_minutes = task.estimated_minutes;
    showScheduleModal.value = true;
};

const submitSchedule = () => {
    if (!activeScheduleTask.value) return;
    scheduleForm.post(`/tasks/${activeScheduleTask.value.id}/schedule`, {
        preserveScroll: true,
        onSuccess: () => {
            showScheduleModal.value = false;
        },
    });
};

const allTasks = computed(() => {
    const list: TaskItem[] = [];
    props.roadmap.phases.forEach(p => {
        p.topics.forEach(t => {
            t.lessons.forEach(l => {
                list.push(...l.tasks);
            });
        });
    });
    return list;
});
</script>

<template>
    <AppLayout :title="roadmap.title">
        <div class="space-y-4">
            
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <Link href="/roadmaps" class="hover:text-indigo-400 flex items-center gap-1">
                    <component :is="isArabic ? ArrowRight : ArrowLeft" class="w-3.5 h-3.5" />
                    <span>{{ isArabic ? 'جميع المسارات' : 'All Roadmaps' }}</span>
                </Link>
                <span>/</span>
                <span class="text-slate-200 font-semibold truncate">{{ roadmap.title }}</span>
            </div>

            <!-- Roadmap Header Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-lg font-bold text-white">{{ roadmap.title }}</h1>
                        <p v-if="roadmap.description" class="text-xs text-slate-400 leading-relaxed">{{ roadmap.description }}</p>
                    </div>

                    <button
                        @click="showAddPhaseModal = true"
                        type="button"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 shrink-0 shadow-md shadow-indigo-600/30"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ isArabic ? 'إضافة مرحلة' : 'Add Phase' }}</span>
                    </button>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5 pt-2 border-t border-slate-800/80">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">
                            {{ progress.completed_tasks }} / {{ progress.total_tasks }} {{ isArabic ? 'مهام مكتملة' : 'tasks completed' }}
                        </span>
                        <span class="font-bold text-indigo-400 font-mono">{{ progress.completion_rate }}%</span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
                        <div 
                            class="bg-indigo-600 h-full rounded-full transition-all duration-500" 
                            :style="{ width: `${progress.completion_rate}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Hierarchy: Phases -> Topics -> Lessons -> Tasks -->
            <div class="space-y-4">
                <div
                    v-for="(phase, phaseIdx) in roadmap.phases"
                    :key="phase.id"
                    class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4.5 space-y-3.5 shadow-sm"
                >
                    <!-- Phase Header -->
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-indigo-600/20 text-indigo-400 font-mono font-bold text-xs flex items-center justify-center border border-indigo-500/20">
                                {{ phaseIdx + 1 }}
                            </span>
                            <h2 class="font-bold text-sm text-slate-100">{{ phase.title }}</h2>
                        </div>
                        <button
                            @click="openAddTopic(phase.id)"
                            type="button"
                            class="text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            <span>{{ isArabic ? 'موضوع' : 'Add Topic' }}</span>
                        </button>
                    </div>

                    <!-- Topics inside Phase -->
                    <div class="space-y-3 ps-3">
                        <div
                            v-for="topic in phase.topics"
                            :key="topic.id"
                            class="bg-slate-950/80 border border-slate-800/80 rounded-xl p-3.5 space-y-3"
                        >
                            <!-- Topic Header -->
                            <div class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <h3 class="font-semibold text-xs text-slate-200">{{ topic.title }}</h3>
                                    <span class="text-[10px] text-slate-500 font-mono">
                                        {{ topic.estimated_minutes }} {{ isArabic ? 'دقيقة تقديرية' : 'estimated mins' }}
                                    </span>
                                </div>
                                <button
                                    @click="openAddLesson(topic.id)"
                                    type="button"
                                    class="text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 px-2 py-1 rounded-lg transition flex items-center gap-1"
                                >
                                    <Plus class="w-3 h-3 text-indigo-400" />
                                    <span>{{ isArabic ? 'درس' : 'Lesson' }}</span>
                                </button>
                            </div>

                            <!-- Lessons & Tasks -->
                            <div class="space-y-2.5 ps-2">
                                <div
                                    v-for="lesson in topic.lessons"
                                    :key="lesson.id"
                                    class="bg-slate-900/60 border border-slate-800/60 rounded-xl p-3 space-y-2"
                                >
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-medium text-slate-300 flex items-center gap-1.5">
                                            <BookOpen class="w-3.5 h-3.5 text-indigo-400" />
                                            <span>{{ lesson.title }}</span>
                                        </h4>
                                        <button
                                            @click="openAddTask(lesson.id)"
                                            type="button"
                                            class="text-[10px] text-indigo-400 hover:text-indigo-300 font-medium"
                                        >
                                            + {{ isArabic ? 'مهمة' : 'Task' }}
                                        </button>
                                    </div>

                                    <!-- Tasks List -->
                                    <div class="space-y-1.5 ps-2">
                                        <div
                                            v-for="task in lesson.tasks"
                                            :key="task.id"
                                            class="bg-slate-950 border border-slate-800/80 rounded-lg p-2.5 flex items-center justify-between gap-3 text-xs"
                                        >
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span 
                                                    class="w-1.5 h-1.5 rounded-full" 
                                                    :class="task.status === 'completed' ? 'bg-emerald-400' : 'bg-slate-500'"
                                                ></span>
                                                <span 
                                                    class="truncate font-medium"
                                                    :class="task.status === 'completed' ? 'line-through text-slate-500' : 'text-slate-200'"
                                                >
                                                    {{ task.title }}
                                                </span>
                                                <span class="text-[10px] text-slate-500 font-mono">({{ task.estimated_minutes }}m)</span>
                                            </div>

                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <button
                                                    @click="openSchedule(task)"
                                                    type="button"
                                                    class="p-1.5 rounded-md bg-slate-800 hover:bg-indigo-600 text-slate-300 hover:text-white transition"
                                                    :title="isArabic ? 'جدولة الجلسة' : 'Schedule to Calendar'"
                                                >
                                                    <Calendar class="w-3.5 h-3.5" />
                                                </button>
                                                <button
                                                    @click="router.post('/tracker/start', { task_id: task.id })"
                                                    type="button"
                                                    class="p-1.5 rounded-md bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white transition"
                                                    :title="isArabic ? 'بدء المذاكرة' : 'Start Study Session'"
                                                >
                                                    <Play class="w-3.5 h-3.5 fill-current" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div v-if="roadmap.phases.length === 0" class="text-center py-12 text-slate-500 text-xs border border-dashed border-slate-800 rounded-2xl">
                    {{ isArabic ? 'لا توجد مراحل في هذا المسار بعد. اضغط "إضافة مرحلة" للبدء.' : 'No phases added yet. Click Add Phase above to start building.' }}
                </div>
            </div>

            <!-- Modals for Building -->
            <!-- Add Phase Modal -->
            <div v-if="showAddPhaseModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-3 text-xs">
                    <h3 class="font-bold text-sm text-white">{{ isArabic ? 'إضافة مرحلة جديدة' : 'Add New Phase' }}</h3>
                    <form @submit.prevent="submitPhase" class="space-y-3">
                        <input
                            v-model="phaseForm.title"
                            type="text"
                            :placeholder="isArabic ? 'عنوان المرحلة...' : 'Phase title...'"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
                            required
                        />
                        <div class="flex justify-end gap-2 pt-2">
                            <button @click="showAddPhaseModal = false" type="button" class="px-3 py-1.5 text-slate-400">Cancel</button>
                            <button type="submit" class="bg-indigo-600 text-white font-semibold px-4 py-1.5 rounded-xl">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Topic Modal -->
            <div v-if="showAddTopicModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-3 text-xs">
                    <h3 class="font-bold text-sm text-white">{{ isArabic ? 'إضافة موضوع جديد' : 'Add New Topic' }}</h3>
                    <form @submit.prevent="submitTopic" class="space-y-3">
                        <input
                            v-model="topicForm.title"
                            type="text"
                            :placeholder="isArabic ? 'عنوان الموضوع...' : 'Topic title...'"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
                            required
                        />
                        <div class="flex justify-end gap-2 pt-2">
                            <button @click="showAddTopicModal = false" type="button" class="px-3 py-1.5 text-slate-400">Cancel</button>
                            <button type="submit" class="bg-indigo-600 text-white font-semibold px-4 py-1.5 rounded-xl">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Task Modal -->
            <div v-if="showAddTaskModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-3 text-xs">
                    <h3 class="font-bold text-sm text-white">{{ isArabic ? 'إضافة مهمة جديدة' : 'Add New Task' }}</h3>
                    <form @submit.prevent="submitTask" class="space-y-3">
                        <input
                            v-model="taskForm.title"
                            type="text"
                            :placeholder="isArabic ? 'عنوان المهمة...' : 'Task title...'"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
                            required
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <select v-model="taskForm.task_type" class="bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-200">
                                <option value="reading">Reading</option>
                                <option value="coding">Coding</option>
                                <option value="practice">Practice</option>
                                <option value="project">Project</option>
                                <option value="review">Review</option>
                            </select>
                            <input
                                v-model.number="taskForm.estimated_minutes"
                                type="number"
                                min="5"
                                max="480"
                                placeholder="Minutes"
                                class="bg-slate-950 border border-slate-800 rounded-xl p-2 text-slate-100"
                            />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button @click="showAddTaskModal = false" type="button" class="px-3 py-1.5 text-slate-400">Cancel</button>
                            <button type="submit" class="bg-indigo-600 text-white font-semibold px-4 py-1.5 rounded-xl">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Schedule Task Modal -->
            <div v-if="showScheduleModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-3 text-xs">
                    <h3 class="font-bold text-sm text-white">{{ isArabic ? 'تحويل المهمة إلى جلسة في التقويم' : 'Convert Task to Calendar Session' }}</h3>
                    <p class="text-slate-400">{{ activeScheduleTask?.title }}</p>
                    <form @submit.prevent="submitSchedule" class="space-y-3">
                        <div>
                            <label class="block text-slate-400 mb-1">{{ isArabic ? 'وقت البدء (UTC)' : 'Start Date & Time (UTC)' }}</label>
                            <input
                                v-model="scheduleForm.start_utc"
                                type="datetime-local"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100 font-mono"
                                required
                            />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button @click="showScheduleModal = false" type="button" class="px-3 py-1.5 text-slate-400">Cancel</button>
                            <button type="submit" class="bg-indigo-600 text-white font-semibold px-4 py-1.5 rounded-xl">Schedule</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
