<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME ?></title>

    <!-- favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('public/assets/images/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('public/assets/images/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('public/assets/images/favicon-16x16.png') ?>">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Simple Notify -->
    <link rel="stylesheet" href="<?= base_url('public/libs/simplenotify/css/simple-notify.min.css') ?>">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/style.css') ?>">

    <script>
        window.baseUrl = "<?= base_url() ?>";

        const appHeight = () => {
            const doc = document.documentElement;
            doc.style.setProperty('--app-height', `${window.innerHeight}px`);
            doc.style.setProperty('--admin-main-content-height', `${window.innerHeight - 70}px`);
        }
        window.addEventListener('resize', appHeight);
        appHeight();
    </script>

    <?= $this->renderSection('head') ?>
</head>

<body>
    
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="layout-width">
                <div class="navbar-header">

                    <div class="d-flex">
                        <div class="header-item">
                            <img src="<?= base_url('public/assets/images/logo.png?v=1') ?>" height="59">
                        </div>
                    </div>

                    <div class="d-flex align-items-center">

                        <div class="header-item" style="background: #f3f3f9;">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?= $user['full_name'] ?>
                            </a>
                            <div style="right: 0; left: unset;" class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="<?= base_url('logout') ?>">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <?= $this->renderSection('content') ?>
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <script>
                                document.write(new Date().getFullYear())
                            </script> © <?= APP_NAME ?>.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                Filling in the gaps
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

    <!-- Simple Notify -->
    <script src="<?= base_url('public/libs/simplenotify/js/simple-notify.min.js') ?>"></script>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="<?= base_url('public/assets/js/functions.js') ?>"></script>

    <?= $this->renderSection('foot') ?>
</body>

</html>