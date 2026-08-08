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
            <h2 class="text-2xl font-bold text-slate-800"><?php echo e($assessment->title); ?></h2>
            <p class="mt-1 text-slate-500">
                <?php echo e($assessment->lesson->course->title); ?> — Attempt #<?php echo e($attempt->attempt_number); ?>

            </p>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-w-4xl mx-auto p-6">

        <div class="mb-6 flex items-center justify-between rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">

            <span>
                Answer all questions, then submit once at the end.
            </span>

            <?php if($assessment->time_limit): ?>
                <span>
                    ⏱ <?php echo e($assessment->time_limit); ?> minute(s)
                </span>
            <?php endif; ?>

            <span>
                Passing score: <?php echo e($assessment->passing_score); ?>%
            </span>

        </div>

        <form
            method="POST"
            action="<?php echo e(route('student.assessments.submit', $assessment)); ?>"
            onsubmit="return confirm('Are you sure you want to submit? You cannot change your answers afterwards.');">

            <?php echo csrf_field(); ?>

            <div class="space-y-6">

                <?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600">
                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-question-mark-circle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-5 w-5']); ?>
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
                                Question <?php echo e($index + 1); ?>

                            </span>

                            <p class="mt-2 text-lg font-medium text-slate-900">
                                <?php echo e($question->question_text); ?>

                            </p>

                        </div>

                        <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">
                            <?php echo e($question->marks); ?> mark(s)
                        </span>

                    </div>

                    <div class="mt-5">

                        <?php if($question->question_type === 'MCQ'): ?>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">

                                <?php $__currentLoopData = ['a', 'b', 'c', 'd']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $letter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                    <?php if($question->{'option_'.$letter}): ?>

                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-indigo-300 hover:bg-indigo-50">

                                            <input
                                                type="radio"
                                                name="answers[<?php echo e($question->id); ?>]"
                                                value="<?php echo e(strtoupper($letter)); ?>"
                                                class="h-4 w-4 text-indigo-600">

                                            <span class="font-semibold text-slate-500"><?php echo e(strtoupper($letter)); ?>.</span>

                                            <span class="text-slate-700"><?php echo e($question->{'option_'.$letter}); ?></span>

                                        </label>

                                    <?php endif; ?>

                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            </div>

                        <?php elseif($question->question_type === 'TrueFalse'): ?>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">

                                <?php $__currentLoopData = ['True', 'False']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-indigo-300 hover:bg-indigo-50">

                                    <input
                                        type="radio"
                                        name="answers[<?php echo e($question->id); ?>]"
                                        value="<?php echo e($option); ?>"
                                        class="h-4 w-4 text-indigo-600">

                                    <span class="text-slate-700"><?php echo e($option); ?></span>

                                </label>

                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            </div>

                        <?php else: ?>

                            <input
                                type="text"
                                name="answers[<?php echo e($question->id); ?>]"
                                placeholder="Type your answer..."
                                class="block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">

                        <?php endif; ?>

                    </div>

                </div>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            </div>

            <div class="mt-8 flex justify-end">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 font-medium text-white transition hover:bg-indigo-700">

                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-paper-airplane'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-5 w-5']); ?>
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

                    Submit Assessment

                </button>

            </div>

        </form>

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
<?php /**PATH C:\Projects\SkillBridge\resources\views/student/assessments/take.blade.php ENDPATH**/ ?>