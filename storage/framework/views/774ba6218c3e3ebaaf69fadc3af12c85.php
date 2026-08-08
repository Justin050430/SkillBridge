<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'value',
    'icon'
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
    'title',
    'value',
    'icon'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

    <div class="flex justify-between items-center">

        <div>

            <p class="text-slate-500 text-sm">
                <?php echo e($title); ?>

            </p>

            <h2 class="text-3xl font-bold mt-2">
                <?php echo e($value); ?>

            </h2>

        </div>

        <div class="text-indigo-600">

            <?php echo e($icon); ?>


        </div>

    </div>

</div><?php /**PATH C:\Projects\SkillBridge\resources\views/components/ui/stat-card.blade.php ENDPATH**/ ?>