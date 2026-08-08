<?php $__env->startSection('title', 'User Management'); ?>

<?php $__env->startSection('header'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'User Management','subtitle' => 'Manage admins, mentors and students.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'User Management','subtitle' => 'Manage admins, mentors and students.']); ?>
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

    <div class="p-6 flex justify-between items-center">

        <form method="GET" class="flex gap-3">

            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'search','value' => request('search'),'placeholder' => 'Search by name, email or role...','class' => 'w-72']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('search')),'placeholder' => 'Search by name, email or role...','class' => 'w-72']); ?>
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
                name="role"
                onchange="this.form.submit()"
                class="rounded-lg border-gray-300">

                <option value="">All Roles</option>

                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                <option
                    value="<?php echo e($role->id); ?>"
                    <?php if(request('role') == $role->id): echo 'selected'; endif; ?>>

                    <?php echo e($role->role_name); ?>


                </option>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            </select>

            <select
                name="status"
                onchange="this.form.submit()"
                class="rounded-lg border-gray-300">

                <?php $__currentLoopData = ['Active', 'Inactive', 'Deleted', 'All']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                <option
                    value="<?php echo e($option); ?>"
                    <?php if(($status ?? 'Active') === $option): echo 'selected'; endif; ?>>

                    <?php echo e($option === 'All' ? 'All Statuses' : $option); ?>


                </option>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            </select>

        </form>

        <a
            href="<?php echo e(route('admin.users.create')); ?>"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl">

            + Add User

        </a>

    </div>
    
    <table class="w-full">

        <thead class="bg-slate-100">

            <tr>

                <th class="text-left p-4">Name</th>
                <th class="text-left p-4">Email</th>
                <th class="text-left p-4">Role</th>
                <th class="text-left p-4">Status</th>
                <th class="text-center p-4">Action</th>

            </tr>

        </thead>

        <tbody>

            <?php if($users->count()): ?>

            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

            <tr class="border-t hover:bg-slate-50">

                <td class="p-4">
                    <?php echo e($user->first_name); ?> <?php echo e($user->last_name); ?>

                </td>

                <td class="p-4">
                    <?php echo e($user->email); ?>

                </td>

                <td class="p-4">

                    <?php
                    $badge = match($user->role->role_name) {
                    'Admin' => 'bg-red-100 text-red-700',
                    'Mentor' => 'bg-indigo-100 text-indigo-700',
                    default => 'bg-emerald-100 text-emerald-700',
                    };
                    ?>

                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo e($badge); ?>">
                        <?php echo e($user->role->role_name); ?>

                    </span>

                </td>

                <td class="p-4">

                    <?php
                    $statusBadge = match($user->status) {
                    'Inactive' => 'bg-amber-100 text-amber-700',
                    'Deleted' => 'bg-red-100 text-red-700',
                    default => 'bg-emerald-100 text-emerald-700',
                    };
                    ?>

                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo e($statusBadge); ?>">
                        <?php echo e($user->status); ?>

                    </span>

                </td>

                <td class="p-4 text-center">

                    <div class="flex justify-center gap-2">

                        <a
                            href="<?php echo e(route('admin.users.edit',$user)); ?>"
                            class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">

                            Edit

                        </a>

                        <?php if($user->status !== 'Deleted'): ?>

                        <a
                            href="<?php echo e(route('admin.users.delete',$user)); ?>"
                            class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">

                            Deactivate

                        </a>

                        <?php endif; ?>

                    </div>

                </td>

            </tr>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php else: ?>

            <tr>

                <td colspan="5" class="py-16 text-center">

                    <div class="text-6xl mb-4">
                        👥
                    </div>

                    <h3 class="text-xl font-semibold text-slate-700">
                        No users found
                    </h3>

                    <p class="text-slate-500 mt-2">
                        Create your first user to get started.
                    </p>

                </td>

            </tr>

            <?php endif; ?>

        </tbody>

    </table>

    <div class="p-6">

        <?php echo e($users->links()); ?>


    </div>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\SkillBridge\resources\views/admin/users/index.blade.php ENDPATH**/ ?>