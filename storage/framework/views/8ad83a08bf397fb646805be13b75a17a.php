

<?php $__env->startSection('title', 'Lesson Management'); ?>

<?php $__env->startSection('header'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Lesson Management','subtitle' => 'Manage the lessons for '.e($course->title).'.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Lesson Management','subtitle' => 'Manage the lessons for '.e($course->title).'.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91a231a9270579fa1ae9246bd51fb785)): ?>
<?php $attributes = $__attributesOriginal91a231a9270579fa1ae9246bd51fb785; ?>
<?php unset($__attributesOriginal91a231a9270579fa1ae9246bd51fb785); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91a231a9270579fa1ae9246bd51fb785)): ?>
<?php $component = $__componentOriginal91a231a9270579fa1ae9246bd51fb785; ?>
<?php unset($__componentOriginal91a231a9270579fa1ae9246bd51fb785); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="mb-4">
    <?php
        // The lesson pages are shared by Admin and Mentor. Send each role back
        // to its own course-management area so navigation respects the same
        // role boundaries as the backend authorization.
        $coursesRoute = Auth::user()->isMentor()
            ? route('mentor.courses')
            : route('admin.courses');
    ?>

    <a
        href="<?php echo e($coursesRoute); ?>"
        class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
        ← Back to Courses
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">

            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'search','value' => request('search'),'placeholder' => 'Search lessons...','class' => 'w-full sm:w-80']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('search')),'placeholder' => 'Search lessons...','class' => 'w-full sm:w-80']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>

            <select
                name="status"
                onchange="this.form.submit()"
                class="rounded-lg border-gray-300">

                <option value="">All Statuses</option>
                <option value="Draft" <?php if(request('status') === 'Draft'): echo 'selected'; endif; ?>>Draft</option>
                <option value="Published" <?php if(request('status') === 'Published'): echo 'selected'; endif; ?>>Published</option>

            </select>

        </form>

        <a
            href="<?php echo e(route('courses.lessons.create', $course)); ?>"
            class="inline-flex items-center justify-center px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition">
            + Add Lesson
        </a>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[820px]">

            <thead class="bg-slate-100">

                <tr>
                    <th class="text-left p-4">Lesson</th>
                    <th class="text-left p-4">Order</th>
                    <th class="text-left p-4">Assessments</th>
                    <th class="text-left p-4">Duration</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-center p-4">Action</th>
                </tr>

            </thead>

            <tbody>

                <?php $__empty_1 = true; $__currentLoopData = $lessons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lesson): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                <?php
                    $statusClasses = match($lesson->status) {
                        'Published' => 'bg-emerald-100 text-emerald-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                ?>

                <tr class="border-t border-slate-200 hover:bg-slate-50">

                    <td class="p-4">
                        <p class="font-medium text-slate-900"><?php echo e($lesson->title); ?></p>
                        <p class="mt-1 text-sm text-slate-500 line-clamp-1"><?php echo e($lesson->content); ?></p>
                    </td>

                    <td class="p-4 text-slate-600">
                        <?php echo e($lesson->order_number); ?>

                    </td>

                    <td class="p-4 text-slate-600">
                        <?php echo e($lesson->assessments_count); ?>

                    </td>

                    <td class="p-4 text-slate-600">
                        <?php if($lesson->estimated_duration): ?>
                            <?php echo e($lesson->estimated_duration); ?> min
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <td class="p-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo e($statusClasses); ?>">
                            <?php echo e($lesson->status); ?>

                        </span>
                    </td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">

                            <a
                                href="<?php echo e(route('courses.lessons.edit', [$course, $lesson])); ?>"
                                class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">
                                Edit
                            </a>

                            <a
                                href="<?php echo e(route('courses.lessons.delete', [$course, $lesson])); ?>"
                                class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                Delete
                            </a>

                        </div>
                    </td>

                </tr>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-document-text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-12 h-12 mx-auto text-slate-300']); ?>
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
                            No lessons found
                        </h3>

                        <p class="mt-2 text-slate-500">
                            Add the first lesson to get started.
                        </p>
                    </td>
                </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <?php if($lessons->hasPages()): ?>

    <div class="p-4 border-t border-slate-200">
        <?php echo e($lessons->links()); ?>

    </div>

    <?php endif; ?>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\SkillBridge\resources\views/lessons/index.blade.php ENDPATH**/ ?>