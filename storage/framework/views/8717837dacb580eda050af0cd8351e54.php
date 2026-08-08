

<?php $__env->startSection('title', 'Questions'); ?>

<?php $__env->startSection('header'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Questions','subtitle' => ''.e($assessment->title).' · '.e($assessment->lesson->course->title).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Questions','subtitle' => ''.e($assessment->title).' · '.e($assessment->lesson->course->title).'']); ?>
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

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

    <div class="flex items-center gap-3">

        <a
            href="<?php echo e(route('assessments.index')); ?>"
            class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">

            &larr; Back to Assessments

        </a>

    </div>

    <a
        href="<?php echo e(route('assessments.questions.create', $assessment)); ?>"
        class="inline-flex items-center justify-center px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition">
        + Add Question
    </a>

</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="overflow-x-auto">

        <table class="w-full min-w-[760px]">

            <thead class="bg-slate-100">

                <tr>
                    <th class="text-left p-4 w-12">#</th>
                    <th class="text-left p-4">Question</th>
                    <th class="text-left p-4">Type</th>
                    <th class="text-left p-4">Marks</th>
                    <th class="text-left p-4">Correct Answer</th>
                    <th class="text-center p-4">Action</th>
                </tr>

            </thead>

            <tbody>

                <?php $__empty_1 = true; $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                <tr class="border-t border-slate-200 hover:bg-slate-50">

                    <td class="p-4 text-slate-500"><?php echo e($question->order_number); ?></td>

                    <td class="p-4">
                        <p class="font-medium text-slate-900 max-w-md"><?php echo e($question->question_text); ?></p>
                    </td>

                    <td class="p-4">
                        <?php
                            $typeClasses = match($question->question_type) {
                                'MCQ' => 'bg-indigo-100 text-indigo-700',
                                'TrueFalse' => 'bg-emerald-100 text-emerald-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        ?>

                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo e($typeClasses); ?>">
                            <?php echo e($question->question_type); ?>

                        </span>
                    </td>

                    <td class="p-4 text-slate-600"><?php echo e($question->marks); ?></td>

                    <td class="p-4 text-slate-600"><?php echo e($question->correct_answer); ?></td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">
                            <a
                                href="<?php echo e(route('assessments.questions.edit', [$assessment, $question])); ?>"
                                class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="<?php echo e(route('assessments.questions.destroy', [$assessment, $question])); ?>"
                                onsubmit="return confirm('Delete this question?')">

                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>

                                <button
                                    type="submit"
                                    class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                    Delete
                                </button>

                            </form>
                        </div>
                    </td>

                </tr>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-question-mark-circle'); ?>
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
                            No questions yet
                        </h3>

                        <p class="mt-2 text-slate-500">
                            Add questions to let students attempt this assessment.
                        </p>

                        <a
                            href="<?php echo e(route('assessments.questions.create', $assessment)); ?>"
                            class="mt-4 inline-flex items-center px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition">
                            + Add Question
                        </a>
                    </td>
                </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="p-6 border-t border-slate-200 flex justify-between items-center text-sm text-slate-500">

        <span><?php echo e($questions->count()); ?> question(s)</span>

        <span>Total marks: <?php echo e($questions->sum('marks')); ?></span>

    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\SkillBridge\resources\views/assessments/questions/index.blade.php ENDPATH**/ ?>