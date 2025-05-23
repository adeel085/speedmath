<?= $this->extend('admin/Layouts/default') ?>

<?= $this->section('head') ?>

<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Timer Settings</h4>

            <div class="page-title-right">

            </div>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div>
                    <div class="row mb-3">
                        <div class="col-3">
                            <span style="white-space: nowrap;">Grade</span>
                        </div>
                        <div class="col-sm-4 col-9">
                            <span style="white-space: nowrap;">Timer in minutes</span>
                        </div>
                    </div>
                    <?php
                    foreach ($grades as $grade) {
                        ?>
                        <div class="row mb-3">
                            <div class="col-3">
                                <span style="white-space: nowrap;display: inline-block;min-width: 40px;text-align: right;"><?= $grade['grade_level'] ?></span>
                            </div>
                            <div class="col-sm-4 col-9">
                                <input data-id="<?= $grade['id'] ?>" type="text" class="form-control timer-input" value="<?= $grade['timer_minutes'] ?>" placeholder="Timer in minutes">
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <div class="d-flex justify-content-end">
                    <button class="btn btn-sm btn-primary" id="saveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script>
    $(() => {
        $("#saveBtn").click(async () => {

            let timers = [];
            let error = false;

            $(".timer-input").each((index, element) => {

                let timeInMinutes = $(element).val();

                if (timeInMinutes == '') {
                    new Notify({
                        title: 'Error',
                        text: "Please enter a timer value for all grades",
                        status: 'error',
                        autoclose: true,
                        autotimeout: 3000
                    });
                    error = true;
                    return;
                }

                timeInMinutes = parseInt(timeInMinutes);    

                if (isNaN(timeInMinutes)) {
                    new Notify({
                        title: 'Error',
                        text: "Timer value must be a number",
                        status: 'error',
                        autoclose: true,
                        autotimeout: 3000
                    });
                    error = true;
                    return;
                }

                if (timeInMinutes <= 0) {
                    new Notify({
                        title: 'Error',
                        text: "Timer value must be greater than 0",
                        status: 'error',
                        autoclose: true,
                        autotimeout: 3000
                    });
                    error = true;
                    return;
                }

                timers.push({
                    id: $(element).data('id'),
                    timer: timeInMinutes
                });
            });
            
            if (error) {
                return;
            }

            let formData = new FormData();
            formData.append('timers', JSON.stringify(timers));
            
            let res = await ajaxCall({
                url: baseUrl + 'admin/timer/save',
                data: formData,
                csrfHeader: '<?= csrf_header() ?>',
                csrfHash: '<?= csrf_hash() ?>'
            });

            if (res.status == 'success') {
                new Notify({
                    title: 'Success',
                    text: "Timer settings saved successfully",
                    status: 'success',
                    autoclose: true,
                    autotimeout: 3000
                });
            }
            else {
                new Notify({
                    title: 'Error',
                    text: "Failed to save the timer settings",
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
            }
        });
    });
</script>
<?= $this->endSection() ?>