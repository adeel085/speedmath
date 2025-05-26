<?= $this->extend('Layouts/default') ?>

<?= $this->section('head') ?>
<style>
    .logo {
        width: 130px;
    }

    .stars-container {
        color: #bfbfbf;
    }
    .stars-item.active {
        color: #f94949;
    }

    .stars-item:nth-child(1) {
        font-size: 30px;
    }
    .stars-item:nth-child(2) {
        font-size: 45px;
    }
    .stars-item:nth-child(3) {
        font-size: 60px;
    }
    .stars-item:nth-child(4) {
        font-size: 45px;
    }
    .stars-item:nth-child(5) {
        font-size: 30px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container pt-3">
    <img src="<?= base_url('public/assets/images/logo.png?v=1') ?>" alt="Logo" class="logo">
</div>

<div class="d-flex flex-column justify-content-center align-items-center pt-5">

    <div class="text-center">
        <h3><?= ($stars > 3) ? "Well done!" : "" ?> You got <?= $stars ?> star<?= ($stars > 1) ? "s" : "" ?></h3>
    </div>

    <div class="stars-container d-flex align-items-end">
        <?php
        for ($i = 1; $i <= 5; $i++) {
            ?>
            <div class="stars-item <?= $i <= $stars ? 'active' : '' ?>">
                <i class="fa fa-star"></i>
            </div>
            <?php
        }
        ?>
    </div>

    <div class="text-center mt-4">
        <a href="<?= base_url('/') ?>" class="btn btn-primary">Continue</a>
    </div>

    <?php
    if ($currentTopic && !empty($currentTopic['tutorial_link'])) {
        ?>
        <div class="text-center mt-4">
            <span><?= $currentTopic['name'] ?> &nbsp; <a target="_blank" href="<?= $currentTopic['tutorial_link'] ?>">Watch Tutorial</a></span>
        </div>
        <?php
    }
    ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script>
    $(document).ready(function() {
    });
</script>
<?= $this->endSection() ?>
