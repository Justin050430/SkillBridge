<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div>
            <h2 class="text-2xl font-bold text-slate-800">My Progress</h2>
            <p class="mt-1 text-slate-500">Track your completion across the courses you are enrolled in.</p>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-5xl mx-auto p-6 space-y-6">

        <?php $__empty_1 = true; $__currentLoopData = $enrollments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

        <?php
            $lessons = $enrollment->course->lessons;
            $totalLessons = $lessons->count();
            $completed = $lessons->filter(fn ($lesson) => ($progressRecords->get($enrollment->id.'-'.$lesson->id)?->status) === 'Completed')->count();
            $percent = $totalLessons > 0 ? round(($completed / $totalLessons) * 100) : 0;
        ?>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        <?php echo e($enrollment->course->title); ?>

                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        <?php echo e($completed); ?> of <?php echo e($totalLessons); ?> lessons completed
                    </p>

                </div>

                <div class="md:w-64">

                    <div class="flex items-center justify-between text-sm font-medium">

                        <span class="text-slate-600">Progress</span>

                        <span class="<?php echo e($percent === 100 ? 'text-emerald-600' : 'text-indigo-600'); ?>">
                            <?php echo e($percent); ?>%
                        </span>

                    </div>

                    <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">

                        <div
                            class="h-full rounded-full <?php echo e($percent === 100 ? 'bg-emerald-500' : 'bg-indigo-500'); ?> transition-all"
                            style="width: <?php echo e($percent); ?>%">
                        </div>

                    </div>

                </div>

            </div>

            <div class="mt-5 divide-y divide-slate-100">

                <?php $__currentLoopData = $lessons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lesson): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                <?php
                    $record = $progressRecords->get($enrollment->id.'-'.$lesson->id);
                    $status = $record?->status ?? 'Not Started';

                    $badge = match ($status) {
                        'Completed' => 'bg-emerald-100 text-emerald-700',
                        'In Progress' => 'bg-amber-100 text-amber-700',
                        default => 'bg-slate-100 text-slate-600',
                    };
                ?>

                <div class="flex items-center justify-between gap-4 py-3">

                    <div class="flex items-center gap-3 min-w-0">

                        <span class="shrink-0 text-sm font-semibold text-slate-400">
                            <?php echo e($lesson->order_number); ?>.
                        </span>

                        <div class="min-w-0">

                            <p class="truncate font-medium text-slate-800">
                                <?php echo e($lesson->title); ?>

                            </p>

                            <p class="text-xs text-slate-500">
                                <?php echo e($status); ?>

                            </p>

                        </div>

                    </div>

                    <div class="flex shrink-0 items-center gap-2">

                        <span class="rounded-full px-3 py-1 text-sm font-medium <?php echo e($badge); ?>">
                            <?php echo e($status); ?>

                        </span>

                        <?php if($status !== 'Completed'): ?>

                            <form method="POST" action="<?php echo e(route('student.progress.update', $lesson)); ?>">
                                <?php echo csrf_field(); ?>

                                <input type="hidden" name="status" value="Completed">

                                <button
                                    type="submit"
                                    class="rounded-lg bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-600 transition hover:bg-indigo-100">
                                    Mark Complete
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                </div>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            </div>

        </div>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

        <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-academic-cap'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mx-auto h-12 w-12 text-slate-300']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>

            <h3 class="mt-4 text-xl font-semibold text-slate-700">
                No enrollments yet
            </h3>

            <p class="mt-2 text-slate-500">
                Enrol in a course to start tracking your progress.
            </p>

            <a
                href="<?php echo e(route('student.courses')); ?>"
                class="mt-6 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 font-medium text-white transition hover:bg-indigo-700">

                Browse Courses

            </a>

        </div>

        <?php endif; ?>

    </div>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Projects\SkillBridge\resources\views/student/progress/index.blade.php ENDPATH**/ ?>