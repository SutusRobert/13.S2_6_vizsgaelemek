!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MagicFridge</title>
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/app.css'); ?> <!-- ha Tailwind-et használsz -->
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white shadow">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="font-bold text-xl text-indigo-600">MagicFridge</a>
            <nav class="space-x-4">
                <a href="<?php echo e(route('login')); ?>" class="text-gray-700 hover:text-indigo-600">Bejelentkezés</a>
                <a href="<?php echo e(route('register')); ?>" class="text-gray-700 hover:text-indigo-600">Regisztráció</a>
            </nav>
        </div>
    </header>

    <!-- Main content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white shadow mt-auto">
        <div class="container mx-auto px-4 py-4 text-center text-gray-500">
            &copy; 2025 MagicFridge
        </div>
    </footer>

</body>
</html><?php /**PATH C:\Users\UdvariDominik\MagicFridge\resources\views/layouts/app.blade.php ENDPATH**/ ?>