<?= $this->extend('Layouts/default_2') ?>

<?= $this->section('head') ?>
<style>
    #chart-container {
        width: 100%;
        max-width: 800px;
        margin: auto;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container" style="margin-top: 40px; margin-bottom: 40px;">
    <div class="row">
        <div class="col-md-12">
            <h4 class="mb-3">My History</h4>

            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <?php
                        if (count($allStudentTopics) > 0) {
                            ?>
                            <select class="form-control" id="topicSelect" style="width: 300px; max-width: 100%;">
                                <option value="" disabled selected>Select a topic</option>
                                <?php
                                foreach ($allStudentTopics as $topic) {
                                    ?>
                                    <option value="<?= $topic['id'] ?>" <?= $filteredTopic && $filteredTopic['id'] == $topic['id'] ? 'selected' : '' ?>><?= $topic['name'] ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <?php
                        }
                        else {
                            ?>
                            <p class="text-muted mb-0">You don't have any topics yet. Talk to your teacher to get assigned a topic.</p>
                            <?php
                        }
                        ?>
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
                        <canvas id="sessionChart"></canvas>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Score</th>
                                    <th>Time Taken</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($studentSessionResults as $studentSessionResult) {
                                    $minutes = floor($studentSessionResult['time_taken'] / 60);
                                    $seconds = $studentSessionResult['time_taken'] % 60;
                                    $totalTime = $minutes . 'm ' . $seconds . 's';
                                    ?>
                                    <tr>
                                        <td><?= $studentSessionResult['correct_count'] ?> / <?= $studentSessionResult['total_questions'] ?></td>
                                        <td><?= $totalTime ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($studentSessionResult['created_at'])) ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <?php
                                if (count($studentSessionResults) == 0) {
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No data found</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
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
        var sessionData = <?= json_encode(array_reverse($studentSessionResults)) ?>;

        if (sessionData.length == 0) {
            $("#chart-container").hide();
            return;
        }

        // Prepare chart data
        const labels = sessionData.map(entry =>
            new Date(entry.created_at).toLocaleString()
        );
        const timeTakenData = sessionData.map(entry =>
            parseInt(entry.time_taken, 10)
        );

        new Chart(document.getElementById('sessionChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                {
                    label: 'Time Taken (seconds)',
                    data: timeTakenData,
                    borderColor: 'green',
                    backgroundColor: 'rgba(0,128,0,0.1)',
                    tension: 0.3,
                    fill: true
                }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2.5,
                scales: {
                y: {
                    beginAtZero: true,
                    title: {
                    display: true,
                    text: 'Time Taken (s)'
                    }
                },
                x: {
                    title: {
                    display: true,
                    text: 'Session Date & Time'
                    }
                }
                }
            }
        });
    });
</script>
<?= $this->endSection() ?>