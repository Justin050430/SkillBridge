<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'role' => 'admin'
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'role' => 'admin'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<aside class="w-64 bg-slate-900 text-white flex flex-col">

    <!-- Logo -->
    <div class="px-6 py-6 border-b border-slate-700">

        <h1 class="text-2xl font-bold">
            SkillBridge
        </h1>

        <p class="text-sm text-slate-400 mt-1">
            Learning Management System
        </p>

    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2">

        <?php echo e($slot); ?>


    </nav>

    <!-- Footer -->
    <div class="border-t border-slate-700 p-4">

        <div class="mb-4">

            <div class="font-semibold">

                <?php echo e(Auth::user()->first_name); ?>

                <?php echo e(Auth::user()->last_name); ?>


            </div>

            <div class="text-sm text-slate-400">

                <?php echo e(Auth::user()->role->role_name); ?>


            </div>

        </div>

        <form method="POST" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>

            <button
                class="w-full rounded-xl bg-red-600 hover:bg-red-700 py-2 transition">

                Logout

            </button>

        </form>

    </div>

</aside><?php /**PATH C:\Projects\SkillBridge\resources\views\components\ui\sidebar.blade.php ENDPATH**/ ?>