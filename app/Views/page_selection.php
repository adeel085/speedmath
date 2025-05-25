<?= $this->extend('Layouts/default_2') ?>

<?= $this->section('head') ?>
<style>
    .chart-row {
        max-width: 1000px;
    }
    #weeklyGoalChart, #yearlyGoalChart {
        width: 300px !important;
        height: 300px !important;
    }
    a .card-title {
        color: #333333;
    }
    a.card:hover {
        text-decoration: none;
    }
    @media (max-width: 767px) {
        .chart-row {
            gap: 40px;
        }
        .page-card {
            width: 100%;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container my-4">
    <div class="row">
        <div class="col-md-12">
            <span>Hi <?= explode(' ', $user['full_name'])[0] ?>,</span>
            <h3>Welcome Back</h3>
        </div>
    </div>

    <div class="mt-4">

        <?php
        if (!$currentTopic) {
            ?>
            <div class="alert alert-warning mb-4">
                <i class="fa fa-exclamation-triangle"></i> <?= !empty($message) ? $message : "No topic for you. Talk to your teacher about this." ?>
            </div>
            <?php
        }
        ?>

        <h4>Quick Links</h4>

        <?php
        if ($currentTopic && !empty($currentTopic['tutorial_link'])) {
            ?>
            <div class="card mb-2">
                <div class="card-body">
                    <span><?= $currentTopic['name'] ?> &nbsp; <a target="_blank" href="<?= $currentTopic['tutorial_link'] ?>">Watch Tutorial</a></span>
                </div>
            </div>
            <?php
        }
        ?>
        <div class="d-flex flex-wrap" style="gap: 10px;">
            <a class="card page-card" style="padding: 40px 60px; cursor: pointer;" href="<?= base_url('home') ?>">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center">
                        <img src="<?= base_url('public/assets/images/session.png') ?>" alt="Start Session" class="img-fluid" style="width: 130px;">
                        <h5 class="card-title">Start Session</h5>
                    </div>
                </div>
            </a>

            <a class="card page-card" style="padding: 40px 60px; cursor: pointer;" href="<?= base_url('history') ?>">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center">
                        <img src="<?= base_url('public/assets/images/history.png') ?>" alt="View History" class="img-fluid" style="width: 130px;">
                        <h5 class="card-title">View History</h5>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<?= $this->endSection() ?>