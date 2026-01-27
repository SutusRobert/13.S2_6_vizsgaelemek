<?php $__env->startSection('content'); ?>

<?php if($errors->any()): ?>
    <div class="mb-4 rounded bg-red-100 text-red-700 p-3">
        <ul class="list-disc list-inside">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('login')); ?>" class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <?php echo csrf_field(); ?>

    <!-- Email -->
    <div class="mb-4">
        <label for="email" class="block font-medium text-gray-700">Email</label>
        <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Password -->
    <div class="mb-4">
        <label for="password" class="block font-medium text-gray-700">Jelszó</label>
        <input id="password" type="password" name="password" required
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Forgot Password -->
    <div class="flex justify-end mb-4">
        <?php if(Route::has('password.request')): ?>
            <a href="<?php echo e(route('password.request')); ?>" class="text-sm text-indigo-600 hover:underline">
                Elfelejtett jelszó?
            </a>
        <?php endif; ?>
    </div>

    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">
        Bejelentkezés
    </button>
</form>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\UdvariDominik\MagicFridge\resources\views/auth/login.blade.php ENDPATH**/ ?>