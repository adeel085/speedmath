<?= $this->extend('admin/Layouts/default') ?>

<?= $this->section('head') ?>

<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Goals Settings</h4>

            <div class="page-title-right">

            </div>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="mb-5 text-muted">
                    <p class="mb-1">Hints:</p>
                    <ul style="margin: 0; padding-left: 12px;">
                        <li>You cannot add a week from the past</li>
                        <li>You cannot add a week that has already been added for a grade</li>
                        <li>Students receive 5 points when they clear level 1</li>
                        <li>Students receive 10 points when they clear level 2</li>
                        <li>Students receive 15 points when they clear level 3</li>
                    </ul>
                </div>

                <div>
                    <div style="max-width: 400px;">
                        <label>Select Grade</label>
                        <div class="d-flex align-items-center" style="gap: 10px;">
                            <select id="grade" class="form-control">
                                <?php foreach ($grades as $grade) : ?>
                                    <option value="<?= $grade['id'] ?>"><?= $grade['grade_level'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="fetchWeeklyGoalsBtnSpinner" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-reponsive mt-4" id="weeklyGoalsTableContainer" style="display: none;">
                    <div class="mb-3">You are currently editing the goals for the grade: <span style="font-weight: 600;" id="weeklyGoalsTableGradeName"></span></div>
                    <table class="table table-sm" id="weeklyGoalsTable">
                        <thead>
                            <tr>
                                <th>Week</th>
                                <th>Goal Points</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="weeklyGoalsTableAddRow">
                                <td>
                                    <input type="week" class="form-control form-control-sm" id="weeklyGoalsTableAddRowWeek">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" id="weeklyGoalsTableAddRowGoalPoints">
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <button class="btn btn-sm btn-primary" id="weeklyGoalsTableAddRowBtn">
                                            <i class="fa fa-plus"></i> Add
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script>
    $(async () => {

        let currentYear = <?= (int)date('o') ?>;
        let currentWeek = <?= (int)date('W') ?>;

        $(document).on('click', '.weekly-goals-table-delete-row-btn', async function() {
            let tr = $(this).closest('tr');
            let weekStartDate = tr.attr('data-week-start-date');
            const gradeId = $("#grade").val();

            if (!gradeId) {
                new Notify({
                    title: 'Error',
                    text: 'Please select a grade',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }
            
            let formData = new FormData();
            formData.append('grade_id', gradeId);
            formData.append('week', weekStartDate);

            $(this).attr('data-content', $(this).html()).html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events', 'none');

            try {
                let res = await ajaxCall({
                    url: baseUrl + 'admin/goals/deleteWeeklyGoal',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status !== 'success') {
                    new Notify({
                        title: 'Error',
                        text: res.message,
                        status: 'error',
                        autoclose: true,
                        autotimeout: 3000
                    });
                }
                else {
                    tr.remove();

                    new Notify({
                        title: 'Success',
                        text: 'Weekly goal deleted successfully',
                        status: 'success',
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

            $(this).html($(this).attr('data-content')).css('pointer-events', 'auto');
        });

        $("#weeklyGoalsTableAddRowBtn").click(async function() {
            
            const gradeId = $("#grade").val();
            let week = $("#weeklyGoalsTableAddRowWeek").val();
            let goalPoints = $("#weeklyGoalsTableAddRowGoalPoints").val();

            if (!week || !goalPoints || !gradeId) {
                new Notify({
                    title: 'Error',
                    text: 'Please fill in all fields',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            if (isPastWeek(week, currentYear, currentWeek)) {
                new Notify({
                    title: 'Error',
                    text: 'You cannot add a week from the past',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            goalPoints = Number(goalPoints);

            if (isNaN(goalPoints)) {
                new Notify({
                    title: 'Error',
                    text: 'Goal points must be a number',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }
            
            if (goalPoints < 0) {
                new Notify({
                    title: 'Error',
                    text: 'Goal points must be positive',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            let weekStartDate = getWeekStartDate(week);

            // Check if the week start date already exists
            if ($(`tr[data-week-start-date="${weekStartDate}"]`).length > 0) {
                new Notify({
                    title: 'Error',
                    text: 'This week has already been added',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            let formData = new FormData();
            formData.append('grade_id', gradeId);
            formData.append('week', weekStartDate);
            formData.append('goal_points', goalPoints);

            $("#weeklyGoalsTableAddRowBtn").attr('data-content', $("#weeklyGoalsTableAddRowBtn").html()).html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events', 'none');

            try {
                let res = await ajaxCall({
                    url: baseUrl + 'admin/goals/addWeeklyGoal',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status == "success") {
                    $(`<tr data-week-start-date="${weekStartDate}">
                            <td>${formatWeekRange(weekStartDate)}</td>
                            <td>${goalPoints}</td>
                            <td>
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-sm btn-danger weekly-goals-table-delete-row-btn">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`).insertBefore($("#weeklyGoalsTableAddRow"));

                    // Reset the input fields
                    $("#weeklyGoalsTableAddRowWeek").val('');
                    $("#weeklyGoalsTableAddRowGoalPoints").val('');
                }
                else {
                    new Notify({
                        title: 'Error',
                        text: res.message || 'Something went wrong',
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

            $("#weeklyGoalsTableAddRowBtn").html($("#weeklyGoalsTableAddRowBtn").attr('data-content')).css('pointer-events', 'auto');
        });
        
        $("#grade").change(async function() {

            $("#fetchWeeklyGoalsBtnSpinner").show();

            await fetchWeeklyGoals();
            
            $("#fetchWeeklyGoalsBtnSpinner").hide();
        });

        $("#grade").change();

        async function fetchWeeklyGoals() {

            $("#weeklyGoalsTableContainer").hide();
            
            const gradeId = $("#grade").val();
            if (!gradeId) {
                new Notify({
                    title: 'Error',
                    text: 'Please select a grade',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            const selectedGradeName = $("#grade").find(`option[value="${gradeId}"]`).text();
            $("#weeklyGoalsTableGradeName").text(selectedGradeName);

            let formData = new FormData();
            formData.append('grade_id', gradeId);

            let res = await ajaxCall({
                url: baseUrl + 'admin/goals/fetchWeeklyGoals',
                data: formData,
                csrfHeader: '<?= csrf_header() ?>',
                csrfHash: '<?= csrf_hash() ?>'
            });

            if (res.status !== 'success') {
                new Notify({
                    title: 'Error',
                    text: res.message,
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            $("#weeklyGoalsTableContainer").show();

            // Remove all existing rows
            $("#weeklyGoalsTable tbody tr[data-week-start-date]").remove();

            let goals = res.data;
        
            goals.forEach(goal => {
                $(`<tr data-week-start-date="${goal.week_start_date}">
                        <td>${formatWeekRange(goal.week_start_date)}</td>
                        <td>${goal.goal_points}</td>
                        <td>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-sm btn-danger weekly-goals-table-delete-row-btn">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>`).insertBefore($("#weeklyGoalsTableAddRow"));
            });
        }
    });

    function isPastWeek(inputWeekString, currentYear, currentWeek) {
        const [inputYearStr, inputWeekStr] = inputWeekString.split("-W");
        const inputYear = parseInt(inputYearStr, 10);
        const inputWeek = parseInt(inputWeekStr, 10);

        if (inputYear < currentYear) return true;
        if (inputYear === currentYear && inputWeek < currentWeek) return true;
        return false;
    }

    function getWeekStartDate(weekString) {
        const [year, week] = weekString.split("-W").map(Number);

        const jan4 = new Date(year, 0, 4);
        const dayOfWeek = jan4.getDay();
        const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        const firstMonday = new Date(jan4);
        firstMonday.setDate(jan4.getDate() + mondayOffset);

        const weekStartDate = new Date(firstMonday);
        weekStartDate.setDate(firstMonday.getDate() + (week - 1) * 7);

        // Format to MySQL date string (YYYY-MM-DD)
        const yyyy = weekStartDate.getFullYear();
        const mm = String(weekStartDate.getMonth() + 1).padStart(2, '0');
        const dd = String(weekStartDate.getDate()).padStart(2, '0');

        return `${yyyy}-${mm}-${dd}`;
    }

    function formatWeekRange(startDateString) {
        const startDate = new Date(startDateString);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6); // Add 6 days

        const formatter = new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'long',
            day: '2-digit'
        });

        const formattedStart = formatter.format(startDate); // e.g. "April 07, 2025"
        const formattedEnd = formatter.format(endDate);     // e.g. "April 14, 2025"

        return `${formattedStart} - ${formattedEnd}`;
    }
</script>
<?= $this->endSection() ?>