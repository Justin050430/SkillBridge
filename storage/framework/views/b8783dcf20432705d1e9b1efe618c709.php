<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Laravel')); ?></title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <?php if (isset($component)) { $__componentOriginale23455676c7ef1e1f29a2d2f27851fc5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale23455676c7ef1e1f29a2d2f27851fc5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skillbridge-logo','data' => ['href' => '/','iconClass' => 'h-10 w-10 text-indigo-600','textClass' => 'text-xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skillbridge-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => '/','icon-class' => 'h-10 w-10 text-indigo-600','text-class' => 'text-xl']); ?>
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
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                <?php echo e($slot); ?>

            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\Projects\SkillBridge\resources\views\layouts\guest.blade.php ENDPATH**/ ?>