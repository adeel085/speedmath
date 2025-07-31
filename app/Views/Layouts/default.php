<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME ?></title>

    <!-- favicon -->
    <link rel="icon" type="image/x-png" href="<?= base_url('public/assets/images/favicon.png') ?>?v=1">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Simple Notify -->
    <link rel="stylesheet" href="<?= base_url('public/libs/simplenotify/css/simple-notify.min.css') ?>">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="<?= base_url('public/assets/css/style.css') ?>">

    <script>
        window.baseUrl = "<?= base_url() ?>";

        const appHeight = () => {
            const doc = document.documentElement;
            doc.style.setProperty('--app-height', `${window.innerHeight}px`);
        }
        window.addEventListener('resize', appHeight);
        appHeight();
    </script>

    <?= $this->renderSection('head') ?>
</head>

<body>
    <?= $this->renderSection('content') ?>

    <!-- Simple Notify -->
    <script src="<?= base_url('public/libs/simplenotify/js/simple-notify.min.js') ?>"></script>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="<?= base_url('public/assets/js/functions.js') ?>"></script>

    <?php if (isset($flashData['status']) && $flashData['status'] == 'user_signed_up'): ?>
    <script>
        new Notify({
            title: 'Success',
            text: 'Your account has been created successfully. Please login to continue.',
            status: 'success',
            autoclose: true,
            autotimeout: 4000
        });
    </script>
    <?php endif; ?>

    <?php if (isset($flashData['status']) && $flashData['status'] == 'teacher_registered'): ?>
    <script>
        new Notify({
            title: 'Success',
            text: 'Teacher account created successfully',
            status: 'success',
            autoclose: true,
            autotimeout: 3000
        });
    </script>
    <?php endif; ?>

    <?= $this->renderSection('foot') ?>
</body>

</html>