<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between">
        <div class="flex items-center gap-6">
            <?php if (isset($component)) { $__componentOriginale23455676c7ef1e1f29a2d2f27851fc5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale23455676c7ef1e1f29a2d2f27851fc5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skillbridge-logo','data' => ['href' => ''.e(route('dashboard')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skillbridge-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('dashboard')).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale23455676c7ef1e1f29a2d2f27851fc5)): ?>
<?php $attributes = $__attributesOriginale23455676c7ef1e1f29a2d2f27851fc5; ?>
<?php unset($__attributesOriginale23455676c7ef1e1f29a2d2f27851fc5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale23455676c7ef1e1f29a2d2f27851fc5)): ?>
<?php $component = $__componentOriginale23455676c7ef1e1f29a2d2f27851fc5; ?>
<?php unset($__componentOriginale23455676c7ef1e1f29a2d2f27851fc5); ?>
<?php endif; ?>

            <?php if(Auth::user()->isStudent()): ?>
                <?php
                    $unreadNotifications = Auth::user()->notifications()->where('is_read', false)->count();
                ?>

                <a
                    href="<?php echo e(route('student.courses')); ?>"
                    class="text-sm font-medium <?php echo e(request()->routeIs('student.courses') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600'); ?>">
                    Browse Courses
                </a>

                <a
                    href="<?php echo e(route('student.assessments.index')); ?>"
                    class="text-sm font-medium <?php echo e(request()->routeIs('student.assessments.*') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600'); ?>">
                    My Assessments
                </a>

                <a
                    href="<?php echo e(route('student.progress')); ?>"
                    class="text-sm font-medium <?php echo e(request()->routeIs('student.progress') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600'); ?>">
                    My Progress
                </a>

                <a
                    href="<?php echo e(route('notifications.index')); ?>"
                    class="text-sm font-medium <?php echo e(request()->routeIs('notifications.*') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600'); ?>">
                    Notifications
                    <?php if($unreadNotifications > 0): ?>
                        <span class="ml-1 inline-flex items-center justify-center rounded-full bg-indigo-600 px-1.5 text-xs font-bold text-white">
                            <?php echo e($unreadNotifications); ?>

                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>

        <div>
            <?php echo e(Auth::user()->first_name); ?> <?php echo e(Auth::user()->last_name); ?>


            <form method="POST" action="<?php echo e(route('logout')); ?>" class="inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="ml-4 text-red-600">
                    Logout
                </button>   
            </form>
        </div>
    </div>
</nav>
<?php /**PATH C:\Projects\SkillBridge\resources\views\layouts\navigation.blade.php ENDPATH**/ ?>