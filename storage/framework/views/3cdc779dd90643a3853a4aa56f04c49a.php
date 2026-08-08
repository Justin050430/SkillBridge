<?php $__env->startSection('title', 'Delete Course'); ?>

<?php $__env->startSection('header'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Delete Course','subtitle' => 'This action cannot be undone.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Delete Course','subtitle' => 'This action cannot be undone.']); ?>
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

<div class="max-w-xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <h2 class="text-xl font-semibold text-red-600">Are you sure?</h2>

        <p class="mt-4 text-slate-600">
            You are about to delete <strong><?php echo e($course->title); ?></strong>.
        </p>

        <?php if($course->lessons_count || $course->enrollments_count): ?>
            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 p-4 text-amber-800">
                This course cannot be deleted because it has lessons or enrollments.
            </div>
        <?php else: ?>
            <p class="mt-2 text-slate-500">This action cannot be undone.</p>
        <?php endif; ?>

        <form
            method="POST"
            action="<?php echo e(route('admin.courses.destroy', $course)); ?>"
            class="mt-8 flex justify-end gap-3">

            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>

            <a
                href="<?php echo e(route('admin.courses')); ?>"
                class="px-5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100">
                Cancel
            </a>

            <?php if(!$course->lessons_count && !$course->enrollments_count): ?>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white">
                    Delete Course
                </button>
            <?php endif; ?>

        </form>

    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\SkillBridge\resources\views\admin\courses\delete.blade.php ENDPATH**/ ?>