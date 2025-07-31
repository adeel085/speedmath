<?= $this->extend('admin/Layouts/default_onboarding') ?>

<?= $this->section('head') ?>
<style>
    
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <p>Welcome to the Onboarding process. Please follow the steps below to get started.</p>

                            <h5 class="mb-3">Create a student</h5>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="name">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="username">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="password">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="grade">Grade <span class="text-danger">*</span></label>
                                        <select class="form-control" id="grade">
                                            <?php foreach ($grades as $grade) : ?>
                                                <option value="<?= $grade['id'] ?>"><?= $grade['grade_level'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="class">Class</label>
                                        <select class="form-control" id="class">
                                            <option value="">Select Class</option>
                                            <?php foreach ($classes as $class) : ?>
                                                <option value="<?= $class['id'] ?>"><?= $class['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="parentEmails">Parent Emails (comma separated)</label>
                                        <input type="text" class="form-control" id="parentEmails">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-sm btn-primary" id="saveBtn">Continue</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script>
    $(() => {

        $('#saveBtn').click(async function(e) {
            
            let name = $('#name').val().trim();
            let username = $('#username').val().trim();
            let email = $('#email').val().trim();
            let password = $('#password').val().trim();
            let grade = $('#grade').val();
            let classId = $('#class').val();
            let parentEmails = $('#parentEmails').val().trim();

            if (!name || !username || !email || !password || !grade) {
                new Notify({
                    status: 'error',
                    title: 'Error',
                    text: 'Please fill all the required (*) fields',
                    timeout: 3000,
                    autoclose: true
                });
                return;
            }

            try {
                let formData = new FormData();
                formData.append('name', name);
                formData.append('username', username);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('grade', grade);
                formData.append('classId', classId);
                formData.append('parentEmails', parentEmails);

                $(this).attr('data-content', $(this).html()).html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events', 'none');

                let response = await ajaxCall({
                    url: baseUrl + '/admin/students/saveNew',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (response.status == 'success') {
                    window.location.reload();
                    return;
                }
                else {
                    new Notify({
                        title: 'Error',
                        text: response.message,
                        status: 'error',
                        autoclose: true,
                        autotimeout: 3000
                    });
                }
            } catch (error) {
                new Notify({
                    title: 'Error',
                    text: error.responseJSON.message || 'Something went wrong',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
            }

            // Reset save button
            $('#saveBtn').html($(this).attr('data-content')).css('pointer-events', 'auto');
        });
    });
</script>
<?= $this->endSection() ?>
