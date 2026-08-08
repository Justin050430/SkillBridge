

<?php $__env->startSection('title', 'Delete Assessment'); ?>

<?php $__env->startSection('header'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Delete Assessment','subtitle' => 'This action cannot be undone.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Delete Assessment','subtitle' => 'This action cannot be undone.']); ?>
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
            You are about to delete <strong><?php echo e($assessment->title); ?></strong>
            from the lesson <strong><?php echo e($assessment->lesson->title); ?></strong>.
        </p>

        <?php if($assessment->questions_count || $assessment->attempts_count): ?>
            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 p-4 text-amber-800">
                This assessment has <?php echo e($assessment->questions_count); ?> question(s) and
                <?php echo e($assessment->attempts_count); ?> attempt(s). Deleting it will also remove
                those questions and attempts.
            </div>
        <?php endif; ?>

        <form
            method="POST"
            action="<?php echo e(route('assessments.destroy', $assessment)); ?>"
            class="mt-8 flex justify-end gap-3">

            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>

            <a
                href="<?php echo e(route('assessments.index')); ?>"
                class="px-5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100">
                Cancel
            </a>

            <button
                type="submit"
                class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white">
                Delete Assessment
            </button>

        </form>

    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\SkillBridge\resources\views\assessments\delete.blade.php ENDPATH**/ ?>