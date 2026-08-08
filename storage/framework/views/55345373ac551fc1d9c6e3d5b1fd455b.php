<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo $__env->yieldContent('title', 'SkillBridge Admin'); ?></title>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>

<body class="bg-slate-100">

<div class="flex h-screen">

    <!-- Sidebar -->
    <?php
        $unreadNotifications = Auth::user()->notifications()->where('is_read', false)->count();
    ?>

    <?php if (isset($component)) { $__componentOriginal724cca1d6cfbd0d9b219a0d1bdb2d9a8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal724cca1d6cfbd0d9b219a0d1bdb2d9a8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

        <?php if(Auth::user()->isAdmin()): ?>

        <?php if (isset($component)) { $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sidebar-item','data' => ['href' => route('admin.dashboard'),'active' => request()->routeIs('admin.dashboard')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sidebar-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.dashboard')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.dashboard'))]); ?>

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-home'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
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

            Dashboard

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $attributes = $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $component = $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sidebar-item','data' => ['href' => route('admin.users'),'active' => request()->routeIs('admin.users')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sidebar-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.users')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.users'))]); ?>

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-users'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
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

            User Management

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $attributes = $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $component = $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sidebar-item','data' => ['href' => route('admin.courses'),'active' => request()->routeIs('admin.courses')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sidebar-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.courses')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.courses'))]); ?>

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-book-open'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
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

            Course Management

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $attributes = $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $component = $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>

        <?php elseif(Auth::user()->isMentor()): ?>

        <?php if (isset($component)) { $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sidebar-item','data' => ['href' => route('mentor.dashboard'),'active' => request()->routeIs('mentor.dashboard')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sidebar-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('mentor.dashboard')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('mentor.dashboard'))]); ?>

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-home'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
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

            Dashboard

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $attributes = $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $component = $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>

        <?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sidebar-item','data' => ['href' => route('assessments.index'),'active' => request()->routeIs('assessments.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sidebar-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('assessments.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('assessments.*'))]); ?>

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-clipboard-document-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
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

            Assessment Management

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $attributes = $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $component = $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sidebar-item','data' => ['href' => route('enrollments.index'),'active' => request()->routeIs('enrollments.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sidebar-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('enrollments.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('enrollments.*'))]); ?>

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-user-group'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
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

            Enrollment Management

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $attributes = $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $component = $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sidebar-item','data' => ['href' => route('notifications.index'),'active' => request()->routeIs('notifications.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sidebar-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('notifications.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('notifications.*'))]); ?>

            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-bell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
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

            Notifications

            <?php if($unreadNotifications > 0): ?>
                <span class="ml-auto inline-flex items-center justify-center rounded-full bg-red-500 px-2 text-xs font-bold text-white">
                    <?php echo e($unreadNotifications); ?>

                </span>
            <?php endif; ?>

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $attributes = $__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__attributesOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3)): ?>
<?php $component = $__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3; ?>
<?php unset($__componentOriginalb11b72e4b31cef32aadf7ea0b2ef46b3); ?>
<?php endif; ?>

     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal724cca1d6cfbd0d9b219a0d1bdb2d9a8)): ?>
<?php $attributes = $__attributesOriginal724cca1d6cfbd0d9b219a0d1bdb2d9a8; ?>
<?php unset($__attributesOriginal724cca1d6cfbd0d9b219a0d1bdb2d9a8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal724cca1d6cfbd0d9b219a0d1bdb2d9a8)): ?>
<?php $component = $__componentOriginal724cca1d6cfbd0d9b219a0d1bdb2d9a8; ?>
<?php unset($__componentOriginal724cca1d6cfbd0d9b219a0d1bdb2d9a8); ?>
<?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">

        <header class="bg-white shadow-sm">

            <div class="px-8 py-6">

                <h2 class="text-3xl font-bold text-slate-800">
                    <?php echo $__env->yieldContent('header'); ?>
                </h2>

                <p class="text-slate-500 mt-1">
                    <?php echo $__env->yieldContent('subtitle'); ?>
                </p>

            </div>

        </header>

        <div class="p-8">

            <?php echo $__env->yieldContent('content'); ?>

        </div>

    </main>

</div>
    
    <?php if(session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info')): ?>

    <?php

    $type = 'success';
    $message = session('success');

    if(session()->has('error')){
    $type='error';
    $message=session('error');
    }

    if(session()->has('warning')){
    $type='warning';
    $message=session('warning');
    }

    if(session()->has('info')){
    $type='info';
    $message=session('info');
    }

    $styles = [
    'success' => 'bg-emerald-600',
    'error' => 'bg-red-600',
    'warning' => 'bg-amber-500',
    'info' => 'bg-blue-600',
    ];

    ?>

    <div
        x-data="{ show:true }"
        x-show="show"
        x-transition
        x-init="setTimeout(()=>show=false,3000)"
        class="fixed top-6 right-6 z-50">

        <div class="<?php echo e($styles[$type]); ?> text-white px-6 py-4 rounded-xl shadow-xl">

            <?php echo e($message); ?>


        </div>

    </div>

    <?php endif; ?>

</body>

</html>
<?php /**PATH C:\Projects\SkillBridge\resources\views\layouts\admin.blade.php ENDPATH**/ ?>