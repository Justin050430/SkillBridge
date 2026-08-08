

<?php $__env->startSection('title', 'Enrollment Management'); ?>

<?php $__env->startSection('header'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Enrollment Management','subtitle' => 'Oversee student enrollments across the courses.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Enrollment Management','subtitle' => 'Oversee student enrollments across the courses.']); ?>
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

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">

            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'search','value' => request('search'),'placeholder' => 'Search by student or course...','class' => 'w-full sm:w-80']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('search')),'placeholder' => 'Search by student or course...','class' => 'w-full sm:w-80']); ?>
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
                <option value="Active" <?php if(request('status') === 'Active'): echo 'selected'; endif; ?>>Active</option>
                <option value="Completed" <?php if(request('status') === 'Completed'): echo 'selected'; endif; ?>>Completed</option>
                <option value="Dropped" <?php if(request('status') === 'Dropped'): echo 'selected'; endif; ?>>Dropped</option>

            </select>

        </form>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[820px]">

            <thead class="bg-slate-100">

                <tr>
                    <th class="text-left p-4">Student</th>
                    <th class="text-left p-4">Course</th>
                    <th class="text-left p-4">Mentor</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-left p-4">Enrolled</th>
                    <th class="text-center p-4">Action</th>
                </tr>

            </thead>

            <tbody>

                <?php $__empty_1 = true; $__currentLoopData = $enrollments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                <?php
                    $statusClasses = match ($enrollment->status) {
                        'Active' => 'bg-emerald-100 text-emerald-700',
                        'Completed' => 'bg-indigo-100 text-indigo-700',
                        default => 'bg-slate-200 text-slate-700',
                    };
                ?>

                <tr class="border-t border-slate-200 hover:bg-slate-50">

                    <td class="p-4">
                        <p class="font-medium text-slate-900">
                            <?php echo e($enrollment->student->first_name); ?> <?php echo e($enrollment->student->last_name); ?>

                        </p>
                        <p class="mt-1 text-sm text-slate-500"><?php echo e($enrollment->student->email); ?></p>
                    </td>

                    <td class="p-4">
                        <p class="text-slate-700"><?php echo e($enrollment->course->title); ?></p>
                        <p class="mt-1 text-sm text-slate-500"><?php echo e($enrollment->course->category->category_name); ?></p>
                    </td>

                    <td class="p-4 text-slate-600">
                        <?php echo e($enrollment->course->mentor->first_name); ?>

                        <?php echo e($enrollment->course->mentor->last_name); ?>

                    </td>

                    <td class="p-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo e($statusClasses); ?>">
                            <?php echo e($enrollment->status); ?>

                        </span>
                    </td>

                    <td class="p-4 text-slate-600">
                        <?php echo e($enrollment->enrolled_at?->format('d M Y')); ?>

                    </td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">

                            <?php if($enrollment->status === 'Active'): ?>

                            <form method="POST" action="<?php echo e(route('enrollments.complete', $enrollment)); ?>">
                                <?php echo csrf_field(); ?>

                                <button
                                    type="submit"
                                    class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">
                                    Complete
                                </button>
                            </form>

                            <?php endif; ?>

                            <?php if(in_array($enrollment->status, ['Active', 'Completed'], true)): ?>

                            <form
                                method="POST"
                                action="<?php echo e(route('enrollments.drop', $enrollment)); ?>"
                                onsubmit="return confirm('Drop this enrollment?')">

                                <?php echo csrf_field(); ?>

                                <button
                                    type="submit"
                                    class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                    Drop
                                </button>
                            </form>

                            <?php endif; ?>

                        </div>
                    </td>

                </tr>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-user-group'); ?>
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
                            No enrollments found
                        </h3>

                        <p class="mt-2 text-slate-500">
                            Enrollments will appear here once students join courses.
                        </p>
                    </td>
                </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="p-6">
        <?php echo e($enrollments->links()); ?>

    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\SkillBridge\resources\views/enrollments/index.blade.php ENDPATH**/ ?>