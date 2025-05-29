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
                    <div class="mb-4 d-flex align-items-center" style="gap: 10px;">
                        <?php
                        if (count($allStudentTopics) > 0) {
                            ?>
                            <span>Topic</span>
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
                        <canvas id="performanceChart"></canvas>
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

        // Prepare data arrays
        const labels = sessionData.map(entry =>
            new Date(entry.created_at).toLocaleString()
        );

        const performanceScores = sessionData.map(entry => {
            const correct = parseInt(entry.correct_count, 10);
            const total = parseInt(entry.total_questions, 10);
            const time = parseInt(entry.time_taken, 10);

            const accuracy = total > 0 ? correct / total : 0;
            const speed = time > 0 ? 1 / time : 0;

            // 🔁 If accuracy < 5%, score is 0
            if (accuracy < 0.05) {
                return 0;
            }

            // 🎯 Weighted accuracy formula
            const score = (accuracy ** 2) * speed * 1000;

            return +score.toFixed(2);
        });

        // Create chart
        new Chart(document.getElementById('performanceChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Performance Score (Accuracy Weighted)',
                    data: performanceScores,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    tension: 0.3,
                    fill: true
                }]
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
                        text: 'Performance Score'
                        }
                    },
                    x: {
                        title: {
                        display: true,
                        text: 'Session Date & Time'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const index = context.dataIndex;
                                const entry = sessionData[index];

                                const correct = entry.correct_count;
                                const total = entry.total_questions;
                                const timeTaken = parseInt(entry.time_taken, 10);
                                const minutes = Math.floor(timeTaken / 60);
                                const seconds = timeTaken % 60;

                                return [
                                    `Performance Score: ${context.formattedValue}`,
                                    `Accuracy: ${correct}/${total}`,
                                    `Time Taken: ${minutes}m ${seconds}s`
                                ];
                            }
                        }
                    }
                }
            }
        });
    });
</script>
<?= $this->endSection() ?>