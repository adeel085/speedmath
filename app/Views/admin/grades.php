<?= $this->extend('admin/Layouts/default') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Grades</h4>

            <div class="page-title-right">

            </div>

        </div>
    </div>
</div>

<div class="modal" id="newGradeModal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">New Grade</h5>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
                <p class="text-muted">Enter the name of the grade. It will automatically create the positive, negative and neutral levels.</p>
				<div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name">
                </div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" id="newGradeSaveBtn">Save</button>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="editGradeModal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Edit Grade</h5>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name_ed">
                </div>

                <input type="hidden" id="grade_name_ed">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" id="editGradeSaveBtn">Update</button>
			</div>
		</div>
	</div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end align-items-center mb-4">
                    <button class="btn btn-sm btn-primary" id="newGradeBtn">
                        <i class="fa fa-plus"></i> New Grade
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $grade) : ?>
                                <tr data-name="<?= $grade['name'] ?>">
                                    <td><?= $grade['name'] ?></td>
                                    <td>
                                        <div class="d-flex justify-content-end" style="gap: 10px;">
                                            <a href="javascript:void(0)" class="table-action-btn edit-grade-btn" data-toggle="tooltip" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0)" class="table-action-btn delete-grade-btn" data-toggle="tooltip" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr style="background-color: #f5f5f5;">
                                    <td colspan="2">
                                        <ul class="list-unstyled" style="padding-left: 20px;margin-bottom: 0;border-left: 3px solid #acacac;">
                                            <li class="row mb-2">
                                                <div class="col-2">
                                                    <span>Grade</span>
                                                </div>
                                                <div class="col-3">
                                                    <span>Number of Questions</span>
                                                </div>
                                                <div class="col-4">
                                                    <span>Topic</span>
                                                </div>
                                            </li>
                                            <?php foreach ($grade['grade_levels'] as $gradeLevel) : ?>
                                                <li data-id="<?= $gradeLevel['id'] ?>" class="text-muted mb-3 row align-items-center">
                                                    <div class="col-2">
                                                        <span><?= $gradeLevel['grade_level'] ?></span>
                                                    </div>
                                                    <div class="col-3">
                                                        <input type="text" style="width: 160px; max-width: 100%;" class="form-control number-of-questions" value="<?= $gradeLevel['number_of_questions'] ?>">
                                                    </div>
                                                    <div class="col-4">
                                                        <select class="form-control grade-topic-select">
                                                            <option value="" disabled <?= $gradeLevel['topic_id'] == '' ? 'selected' : '' ?>>Select Topic</option>
                                                            <?php foreach ($topics as $topic) : ?>
                                                                <option value="<?= $topic['id'] ?>" <?= $gradeLevel['topic_id'] == $topic['id'] ? 'selected' : '' ?>><?= $topic['name'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-3">
                                                        <button class="btn btn-sm btn-primary save-grade-btn">Save Changes</button>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($grades) == 0) : ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No grades found</td>
                                </tr>
                            <?php endif; ?>
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
    $(document).ready(function() {

        $('.save-grade-btn').click(async function() {

            console.log("Saved");

            let topicId = $(this).closest("li").find(".grade-topic-select").val();
            let numberOfQuestions = $(this).closest("li").find(".number-of-questions").val();
            let gradeLevelId = $(this).closest('li').data('id');
            
            try {

                let formData = new FormData();
                formData.append('grade_level_id', gradeLevelId);
                formData.append('number_of_questions', numberOfQuestions);
                formData.append('topic_id', topicId);

                let res = await ajaxCall({
                    url: baseUrl + 'admin/grades/save-settings',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status == 'success') {
                    new Notify({
                        title: 'Success',
                        text: res.message,
                        status: 'success',
                        autoclose: true,
                        autotimeout: 3000
                    });
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
        });

        $('.edit-grade-btn').click(function() {
            
            let $tr = $(this).closest('tr');

            let name = $tr.data('name');
            
            $('#name_ed').val(name);
            $('#grade_name_ed').val(name);
            
            $('#editGradeModal').modal('show');
        });

        $('#editGradeSaveBtn').click(async function() {
            let name = $('#name_ed').val();
            let gradeName = $('#grade_name_ed').val();
            
            try {

                let formData = new FormData();
            
                formData.append('grade_name', gradeName);
                formData.append('new_name', name);

                let res = await ajaxCall({
                    url: baseUrl + 'admin/grades/update',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status == 'success') {
                    window.location.reload();
                    return;
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
        });

        $('.delete-grade-btn').click(async function() {
            let name = $(this).closest('tr').data('name');
            
            if (!confirm('Are you sure you want to delete this grade?')) {
                return;
            }

            try {

                let formData = new FormData();
                formData.append('name', name);

                let res = await ajaxCall({
                    url: baseUrl + 'admin/grades/delete',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status == 'success') {
                    window.location.reload();
                    return;
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
        });

        $('#newGradeBtn').click(function() {
            $("#newGradeModal").modal('show');
        });

        $('#newGradeSaveBtn').click(async function() {
            let name = $('#name').val();
            
            if (name == '') {
                new Notify({
                    status: 'error',
                    title: 'Error',
                    text: 'Please enter a name',
                    timeout: 3000,
                    autoclose: true
                });
                return;
            }

            try {

                let formData = new FormData();
                formData.append('name', name);

                // Show loader in button
                $('#newGradeSaveBtn').attr('data-content', $('#newGradeSaveBtn').html()).html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events', 'none');

                let res = await ajaxCall({
                    url: baseUrl + '/admin/grades/saveNew',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status == 'success') {
                    window.location.reload();
                    return;
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

            // Reset login button
            $('#newGradeSaveBtn').html($(this).attr('data-content')).css('pointer-events', 'auto');
        });
    });
</script>
<?= $this->endSection() ?>