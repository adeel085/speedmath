<?= $this->extend('admin/Layouts/default') ?>

<?= $this->section('head') ?>
<style>
    .live-progress-div {
        display: flex;
    }
    .live-progress-class-list {
        list-style: none;
        padding: 0;
        margin: 0;
        border-right: 1px solid #e0e0e0;
        background-color: #f0f0f0;
    }
    .live-progress-class-list li {
        padding: 10px;
        width: 130px;
        border-bottom: 1px solid #e0e0e0;
        cursor: pointer;
        background-color: #f0f0f0;
    }
    .live-progress-class-list li:last-child {
        border-bottom: none;
    }
    .live-progress-class-list li.active {
        background-color: #828282;
        color: #fff;
    }
    .live-progress-class-content {
        flex: 1;
        padding: 10px;
        padding-top: 0;
        overflow-y: auto;
    }

    .overall-progress-div {
        display: flex;
    }
    .overall-progress-class-list {
        list-style: none;
        padding: 0;
        margin: 0;
        border-right: 1px solid #e0e0e0;
        background-color: #f0f0f0;
    }
    .overall-progress-class-list li {
        padding: 10px;
        width: 130px;
        border-bottom: 1px solid #e0e0e0;
        cursor: pointer;
        background-color: #f0f0f0;
    }
    .overall-progress-class-list li:last-child {
        border-bottom: none;
    }
    .overall-progress-class-list li.active {
        background-color: #828282;
        color: #fff;
    }
    .overall-progress-class-content {
        flex: 1;
        padding: 10px;
        padding-top: 0;
        overflow-y: auto;
    }

    .accordion-item {
        margin-bottom: 10px;
        border: 1px solid #c0c0c0;
    }
    .accordion-header {
        padding: 10px;
    }
    .accordion-body {
        padding: 10px;
        padding-left: 45px;
        background-color: #f0f0f0;
        position: relative;
    }
    .accordion-body::before {
        content: '';
        position: absolute;
        left: 25px;
        top: 10px;
        width: 3px;
        height: calc(100% - 20px);
        background-color: #9a9a9a;
        border-radius: 12px;
    }

    .progress-item:not(:last-child) {
        margin-bottom: 10px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Admin Dashboard</h4>

            <div class="page-title-right">

            </div>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-4">Latest Sessions of Students</p>

                <?php
                if (count($classes) == 0) {
                    echo '<div class="alert alert-info">No classes found</div>';
                }
                else {
                ?>
                <div class="live-progress-div">
                    <ul class="live-progress-class-list">
                        <?php foreach ($classes as $index => $class) : ?>
                            <li class="<?= $index == 0 ? 'active' : '' ?> live-progress-class-list-item" data-class-id="<?= $class['id'] ?>">
                                <?= $class['name'] ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="live-progress-class-content">
                        <?php
                        foreach ($classes as $index => $class) {
                            ?>
                            <div id="classLiveProgress_<?= $class['id'] ?>" style="display: <?= $index == 0 ? 'block' : 'none' ?>;">
                                <h5 class="mb-3"><?= $class['name'] ?></h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Status</th>
                                                <th>Correct</th>
                                                <th style="min-width: 200px;">Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($class['students'] as $student) : ?>
                                                <?php
                                                $session = $student['session'];
                                                ?>
                                                <tr>
                                                    <td><?= $student['username'] ?></td>
                                                    <td>
                                                        <?php
                                                            if ($session == null) {
                                                                ?>
                                                                <span class="badge bg-danger" style="color: #fff;">Not started</span>
                                                                <?php
                                                            }
                                                            else {
                                                                $progress_percentage = ($session['correct_count'] + $session['incorrect_count']) / 25 * 100;
                                                                if ($progress_percentage >= 100) {
                                                                    ?>
                                                                    <span class="badge bg-success" style="color: #fff;">Completed</span>
                                                                    <?php
                                                                }
                                                                else {
                                                                    if ($session['completed'] == 1) {
                                                                        ?>
                                                                        <span class="badge bg-success" style="color: #fff;">Completed</span>
                                                                        <?php
                                                                    }
                                                                    else {
                                                                        ?>
                                                                        <span class="badge bg-warning">In Progress</span>
                                                                        <?php
                                                                    }
                                                                }
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ($session == null) {
                                                            echo '<span class="text-muted">N/A</span>';
                                                        }
                                                        else {
                                                            echo $session['correct_count'] . ' / ' . 25;
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ($session == null) {
                                                            echo '<span class="text-muted">N/A</span>';
                                                        }
                                                        else {
                                                            $progress_percentage = ($session['correct_count'] + $session['incorrect_count']) / 25 * 100;
                                                            ?>
                                                            <!-- Progress Bar -->
                                                            <div class="progress">
                                                                <div class="progress-bar" role="progressbar" style="width: <?= $progress_percentage ?>%;" aria-valuenow="<?= $progress_percentage ?>" aria-valuemin="0" aria-valuemax="100"><?= number_format($progress_percentage, 2) ?>%</div>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php
                                            if (count($class['students']) == 0) {
                                                ?>
                                                <tr>
                                                    <td colspan="4" class="text-muted">No students found in this class</td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-3">View weekly session report for a class</p>
                <div class="d-flex flex-wrap align-items-center mb-3" style="gap: 12px;">
                    <label class="mb-0">
                        <input type="radio" name="weeklyReportType" value="last7days" checked> Last 7 days
                    </label>

                    <label class="mb-0">
                        <input type="radio" name="weeklyReportType" value="selectWeek"> Select Week
                    </label>

                    <div style="max-width: 200px; margin-left: 20px;">
                        <input class="form-control " type="week" id="weeklyReportWeek" style="display: none;">
                    </div>
                </div>
                <div class="d-flex" style="gap: 10px;">
                    <div style="flex: 1;">
                        <select class="form-control" id="classSelect">
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class) : ?>
                                <option value="<?= $class['id'] ?>"><?= $class['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button class="btn btn-primary" id="viewReportBtn">View Report</button>
                    </div>
                </div>

                <div class="table-responsive mt-4" id="reportContainer"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-4">Overall Progress of Students</p>

                <?php
                if (count($classes) == 0) {
                    echo '<div class="alert alert-info">No classes found</div>';
                }
                else {
                ?>
                <div class="overall-progress-div">
                    <ul class="overall-progress-class-list">
                        <?php foreach ($classes as $index => $class) : ?>
                            <li class="<?= $index == 0 ? 'active' : '' ?> overall-progress-class-list-item" data-class-id="<?= $class['id'] ?>">
                                <?= $class['name'] ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="overall-progress-class-content">
                    <?php
                        foreach ($classes as $index => $class) {
                            ?>
                            <div id="classOverallProgress_<?= $class['id'] ?>" style="display: <?= $index == 0 ? 'block' : 'none' ?>;">
                                <h5 class="mb-3"><?= $class['name'] ?></h5>
                                <div>
                                    <?php
                                    foreach ($class['students'] as $student) {
                                        ?>
                                        <div class="accordion-item">
                                            <div class="accordion-header d-flex justify-content-between" id="heading-<?= $student['id'] ?>">
                                                <span><?= $student['username'] ?></span>
                                                <span class="text-muted">Grade <?= $student['grade']['grade_level'] ?></span>
                                            </div>
                                            <div class="accordion-body">
                                                <?php
                                                foreach ($student['progress'] as $progress) {
                                                    if (!isset($progress['topic'])) {
                                                        continue;
                                                    }
                                                    ?>
                                                    <div class="progress-item">
                                                        <span><?= $progress['topic']['name'] ?> &nbsp; &nbsp; Level <?= $progress['level'] ?></span>
                                                        &nbsp; &nbsp;
                                                        <!-- Complete or In Progress badge -->
                                                        <span class="badge <?= $progress['completed'] ? 'bg-success' : 'bg-info' ?>" style="color: #fff;">
                                                            <?= $progress['completed'] ? 'Completed' : 'In Progress' ?>
                                                        </span>
                                                    </div>
                                                    <?php
                                                }
                                                if (count($student['progress']) == 0) {
                                                    ?>
                                                    <div class="text-muted">No progress</div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    if (count($class['students']) == 0) {
                                        ?>
                                        <div class="text-muted">No students found in this class</div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script>
    $(() => {

        $("input[name='weeklyReportType']").change(function() {
            if ($(this).val() == 'selectWeek') {
                $("#weeklyReportWeek").show();
            }
            else {
                $("#weeklyReportWeek").hide();
            }
        });

        $(".live-progress-class-list-item").click(function() {
            let classId = $(this).data('class-id');
            $(".live-progress-class-list-item").removeClass('active');
            $(this).addClass('active');

            $(".live-progress-class-content > div").hide();
            $("#classLiveProgress_" + classId).show();
        });

        $(".overall-progress-class-list-item").click(function() {
            let classId = $(this).data('class-id');
            $(".overall-progress-class-list-item").removeClass('active');
            $(this).addClass('active');

            $(".overall-progress-class-content > div").hide();
            $("#classOverallProgress_" + classId).show();
        });

        $("#viewReportBtn").click(async () => {

            let weeklyReportType = $("input[name='weeklyReportType']:checked").val();

            let week = null;

            if (weeklyReportType == 'selectWeek') {
                week = $("#weeklyReportWeek").val();

                if (week == "") {
                    new Notify({
                        status: 'error',
                        title: 'Input Error',
                        text: 'Please select a week',
                        timeout: 3000,
                        autoclose: true
                    });
                    return;
                }
            }

            const classId = $("#classSelect").val();

            if (classId == "") {
                new Notify({
                    status: 'error',
                    title: 'Input Error',
                    text: 'Please select a class',
                    timeout: 3000,
                    autoclose: true
                });
                return;
            }

            try {

                let formData = new FormData();
                formData.append('classId', classId);
                if (weeklyReportType == 'selectWeek') {
                    formData.append('week', week);
                }

                // Show loader in button
                $('#viewReportBtn').attr('data-content', $('#viewReportBtn').html()).html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events', 'none');
                
                let res = await ajaxCall({
                    url: baseUrl + '/admin/dashboard/viewReport',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status == 'success') {
                    let students = res.data.students;

                    if (students.length == 0) {
                        $("#reportContainer").html(`<div class="alert alert-info">No students found in this class</div>`);
                        $('#viewReportBtn').html($('#viewReportBtn').attr('data-content')).css('pointer-events', 'auto');
                        return;
                    }

                    let days = Object.keys(students[0].sessions);
                    
                    $("#reportContainer").html(
                        `
                        <h5>Showing ${weeklyReportType == 'last7days' ? 'last 7 days' : 'week ' + week}</h5>
                        <p class="text-muted mb-3">The following table shows the percentage of each student's sessions for each day</p>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    ${days.map(day => `<th>${day}</th>`).join('')}
                                    ${weeklyReportType == 'selectWeek' ? `<th>Weekly Goal</th>` : ''}
                                </tr>
                            </thead>
                            <tbody>
                                ${students.map(student => `<tr>
                                    <td>${student.username}</td>
                                    ${days.map(day => `<td>${student.sessions[day].percentage ? student.sessions[day].percentage + '%' : '-'}</td>`).join('')}
                                    ${weeklyReportType == 'selectWeek' ? `<td>${student.current_week_points}/${student.weekly_goal}</td>` : ''}
                                </tr>`).join('')}
                            </tbody>
                        </table>`);
                }
                else {
                    new Notify({
                        title: 'Error',
                        text: res.message,
                        status: 'error',
                        autoclose: true,
                        autotimeout: 3000
                    });
                }
            }
            catch (err) {
                new Notify({
                    title: 'Error',
                    text: err.responseJSON.message || 'Something went wrong',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
            }

            // Reset button
            $('#viewReportBtn').html($('#viewReportBtn').attr('data-content')).css('pointer-events', 'auto');
        });
    });
</script>
<?= $this->endSection() ?>