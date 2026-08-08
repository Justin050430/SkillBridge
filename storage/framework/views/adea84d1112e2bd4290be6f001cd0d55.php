<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <h2 class="text-lg font-semibold text-slate-800">
            Notifications
        </h2>

        <?php if($notifications->where('is_read', false)->count() > 0): ?>

            <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>">

                <?php echo csrf_field(); ?>

                <button
                    type="submit"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">

                    Mark all as read

                </button>

            </form>

        <?php endif; ?>

    </div>

    <div class="divide-y divide-slate-100">

        <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

        <?php
            $typeClasses = match ($notification->notification_type) {
                'Course' => 'bg-indigo-100 text-indigo-700',
                'Lesson' => 'bg-amber-100 text-amber-700',
                'Assessment' => 'bg-emerald-100 text-emerald-700',
                'Enrollment' => 'bg-blue-100 text-blue-700',
                default => 'bg-slate-100 text-slate-600',
            };
        ?>

        <div class="flex items-start justify-between gap-4 p-6 <?php echo e($notification->is_read ? '' : 'bg-indigo-50/50'); ?>">

            <div class="min-w-0">

                <div class="flex flex-wrap items-center gap-2">

                    <p class="font-semibold <?php echo e($notification->is_read ? 'text-slate-600' : 'text-slate-900'); ?>">
                        <?php echo e($notification->title); ?>

                    </p>

                    <?php if(! $notification->is_read): ?>
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-medium text-white">
                            New
                        </span>
                    <?php endif; ?>

                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($typeClasses); ?>">
                        <?php echo e($notification->notification_type); ?>

                    </span>

                </div>

                <p class="mt-1 text-sm text-slate-600">
                    <?php echo e($notification->message); ?>

                </p>

                <p class="mt-1 text-xs text-slate-400">
                    <?php echo e($notification->created_at->diffForHumans()); ?>

                </p>

            </div>

            <?php if(! $notification->is_read): ?>

                <form method="POST" action="<?php echo e(route('notifications.read', $notification)); ?>" class="shrink-0">

                    <?php echo csrf_field(); ?>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600 transition hover:bg-slate-200">

                        Mark read

                    </button>

                </form>

            <?php endif; ?>

        </div>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

        <div class="py-16 text-center">

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bell'); ?>
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
                No notifications yet
            </h3>

            <p class="mt-2 text-slate-500">
                Updates about your courses, assessments and enrollments will appear here.
            </p>

        </div>

        <?php endif; ?>

    </div>

    <div class="p-6">
        <?php echo e($notifications->links()); ?>

    </div>

</div>
<?php /**PATH C:\Projects\SkillBridge\resources\views/notifications/_list.blade.php ENDPATH**/ ?>