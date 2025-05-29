<?= $this->extend('admin/Layouts/default') ?>

<?= $this->section('head') ?>
<style>
    
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Class Reports</h4>

            <div class="page-title-right">

            </div>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="mb-3">Report of Class: <?= $class['name'] ?></h6>
                </div>
                <div class="mb-4 d-flex align-items-center" style="gap: 10px;">
                    <span>Topic</span>
                    <select class="form-control" id="topicSelect" style="width: 300px; max-width: 100%;">
                        <option value="" disabled selected>Select a topic</option>
                        <?php
                        foreach ($topics as $topic) {
                            ?>
                            <option value="<?= $topic['id'] ?>" <?= $filteredTopic && $filteredTopic['id'] == $topic['id'] ? 'selected' : '' ?>><?= $topic['name'] ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <?php
                    $minutes = floor($averageTimeTaken / 60);
                    $seconds = $averageTimeTaken % 60;
                    $averageTimeTaken = $minutes . 'm ' . $seconds . 's';

                    $minutes = floor($bestTimeTaken / 60);
                    $seconds = $bestTimeTaken % 60;
                    $bestTimeTaken = $minutes . 'm ' . $seconds . 's';

                    $minutes = floor($worstTimeTaken / 60);
                    $seconds = $worstTimeTaken % 60;
                    $worstTimeTaken = $minutes . 'm ' . $seconds . 's';
                    ?>
                    <span class="d-block"><b style="color: #139f13; font-weight: 500;">Best time taken:</b> <?= $bestTimeTaken ?></span>
                    <span class="d-block"><b style="color: #cb0f0f; font-weight: 500;">Worst time taken:</b> <?= $worstTimeTaken ?></span>
                    <span class="d-block"><b style="font-weight: 500;">Average time taken:</b> <?= $averageTimeTaken ?></span>
                    <?php
                    ?>
                </div>

                <div id="chart-container" class="mb-4">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(() => {
        drawGraph();

        function drawGraph() {
            var performanceData = <?= json_encode($performanceData) ?>;

            if (performanceData.length == 0) {
                $("#chart-container").hide();
                return;
            }

            const labels = performanceData.map(item => item.date);
            const scores = performanceData.map(item => item.average_score);

            const ctx = document.getElementById('performanceChart').getContext('2d');

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Average Score',
                        data: scores,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Class Performance Over Time',
                            font: {
                                size: 18
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Average Score'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });
        }
        
        $("#topicSelect").on("change", (e) => {
            const topicId = $(e.target).val();
            window.location.href = "<?= base_url('/admin/classes/reports/' . $class['id']) ?>?topic=" + topicId;
        });
    });
</script>
<?= $this->endSection() ?>