<?php $__env->startSection('content'); ?>

<form method="POST" action="<?php echo e(route('register')); ?>" class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <?php echo csrf_field(); ?>

    <!-- Name -->
    <div class="mb-4">
        <label for="name" class="block font-medium text-gray-700">Név</label>
        <input id="name" type="text" name="name" value="<?php echo e(old('name')); ?>" required autofocus
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Email -->
    <div class="mb-4">
        <label for="email" class="block font-medium text-gray-700">Email</label>
        <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Password -->
    <div class="mb-4">
        <label for="password" class="block font-medium text-gray-700">Jelszó</label>
        <input id="password" type="password" name="password" required
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <!-- Confirm Password -->
    <div class="mb-4">
        <label for="password_confirmation" class="block font-medium text-gray-700">Jelszó megerősítése</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required
            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-500">
    </div>

    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">
        Regisztráció
    </button>
</form>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\UdvariDominik\MagicFridge\resources\views/auth/register.blade.php ENDPATH**/ ?>