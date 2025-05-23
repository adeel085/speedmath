<?= $this->extend('admin/Layouts/default') ?>

<?= $this->section('content') ?>

<div class="modal" id="topicWizardModal">
    <div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Topic Wizard</h5>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
                <p class="text-muted">You can use the topic wizard to create a new topic by combining the questions from existing topics.</p>

                <div class="mb-4">
                    <div class="form-group">
                        <label for="topicWizardName">Topic Name</label>
                        <input type="text" class="form-control form-control-sm" id="topicWizardName" placeholder="Topic Name">
                    </div>
                </div>

                <div class="d-flex justify-content-end flex-wrap mb-4" style="gap: 10px;">

                    <!-- Select Topic -->
                    <div>
                        <select class="form-control form-control-sm" id="topicWizardSelect">
                            <option value="">Select Topic</option>
                            <?php foreach ($topics as $topic) : ?>
                                <option value="<?= $topic['id'] ?>"><?= $topic['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Select Level -->
                    <div>
                        <select class="form-control form-control-sm" id="topicWizardLevel">
                            <option value="">Select Difficulty Level</option>
                            <option value="1">Level 1 - Easy</option>
                            <option value="2">Level 2 - Medium</option>
                            <option value="3">Level 3 - Hard</option>
                        </select>
                    </div>

                    <!-- Max Questions Count -->
                    <div>
                        <input class="form-control form-control-sm" id="topicWizardMaxQuestionsCount" placeholder="Max Questions Count">
                    </div>

                    <button class="btn btn-sm btn-outline-primary" id="topicWizardAddBtn">
                        <i class="fa fa-plus"></i> Add Topic
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm" id="topicWizardTable">
                        <thead>
                            <tr>
                                <th style="font-weight: 400;">Topic</th>
                                <th style="font-weight: 400;">Difficulty Level</th>
                                <th style="font-weight: 400;">Max Questions</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="topicWizardNoTopics">
                                <td colspan="4" class="text-center text-muted">No topics added yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-sm btn-primary" id="topicWizardCreateBtn">Create Topic</button>
			</div>
		</div>
	</div>
</div>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Topics</h4>

            <div class="page-title-right">

            </div>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end align-items-center mb-4" style="gap: 10px;">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#topicWizardModal">
                        <i class="fa fa-hat-wizard"></i> Topic Wizard
                    </button>
                    <a href="<?= base_url('/admin/topics/new') ?>" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> New Topic
                    </a>
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
                            <?php foreach ($topics as $topic) : ?>
                                <tr data-id="<?= $topic['id'] ?>">
                                    <td><?= $topic['name'] ?></td>
                                    <td>
                                        <div class="d-flex justify-content-end table-action-btn" style="gap: 10px;">
                                            <a href="<?= base_url('/admin/topics/' . $topic['id'] . '/questions') ?>" class="table-action-btn" data-toggle="tooltip" title="Questions List">
                                                <i class="fa fa-align-left"></i>
                                            </a>
                                            <a href="<?= base_url('/admin/topics/edit/' . $topic['id']) ?>" class="table-action-btn" data-toggle="tooltip" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0)" class="table-action-btn delete-btn" data-toggle="tooltip" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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

    function removeTopicFromWizard(e) {
        let $tr = $(e).closest('tr');
        $tr.remove();

        if ($("#topicWizardTable .topic-wizard-row").length == 0) {
            $("#topicWizardNoTopics").show();
        }
    }

    $(() => {

        $("#topicWizardCreateBtn").click(async function(e) {

            let topicName = $("#topicWizardName").val();
            let topics = [];

            if (!topicName) {
                new Notify({
                    title: 'Error',
                    text: 'Please enter a topic name',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            $("#topicWizardTable .topic-wizard-row").each(function() {
                let $tr = $(this);
                topics.push({
                    topicId: $tr.data('topic-id'),
                    level: $tr.data('level'),
                    maxQuestionsCount: $tr.data('max-questions-count')
                });
            });

            // send the topics to the server
            try {

                let formData = new FormData();
                formData.append('topics', JSON.stringify(topics));
                formData.append('topicName', topicName);

                $(this).attr('data-content', $(this).html()).html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events', 'none');

                let res = await ajaxCall({
                    url: baseUrl + 'admin/topics/create-from-wizard',
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

            // reset the button
            $(this).html($(this).attr('data-content')).css('pointer-events', 'auto');
        });

        $("#topicWizardAddBtn").click(function(e) {

            let topicId = $("#topicWizardSelect").val();
            let topicName = $("#topicWizardSelect option:selected").text();
            let level = $("#topicWizardLevel").val();
            let maxQuestionsCount = $("#topicWizardMaxQuestionsCount").val();
            
            if (!topicId) {
                new Notify({
                    title: 'Error',
                    text: 'Please select a topic',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            if (!level) {
                new Notify({
                    title: 'Error',
                    text: 'Please select a difficulty level',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
            }

            if (!maxQuestionsCount) {
                new Notify({
                    title: 'Error',
                    text: 'Please enter a max questions count',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }
            
            maxQuestionsCount = parseInt(maxQuestionsCount);

            if (maxQuestionsCount < 1) {
                new Notify({
                    title: 'Error',
                    text: 'Max questions count must be greater than 0',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            // Check if the tpoicId and level already exists in the table
            if ($("#topicWizardTable .topic-wizard-row").filter(function() {
                return $(this).data('topic-id') == topicId && $(this).data('level') == level;
            }).length > 0) {
                new Notify({
                    title: 'Error',
                    text: 'This topic and level already exists',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            $("#topicWizardNoTopics").hide();

            $("#topicWizardTable").append(`
                <tr class="topic-wizard-row" data-topic-id="${topicId}" data-level="${level}" data-max-questions-count="${maxQuestionsCount}">
                    <td>${topicName}</td>
                    <td>Level ${level}</td>
                    <td>${maxQuestionsCount}</td>
                    <td>
                        <div class="text-right">
                            <i class="fa fa-trash" onclick="removeTopicFromWizard(this)" style="cursor: pointer;"></i>
                        </div>
                    </td>
                </tr>
            `);

            // reset the input fields
            $("#topicWizardSelect").val('');
            $("#topicWizardLevel").val('');
            $("#topicWizardMaxQuestionsCount").val('');
        });

        $('.delete-btn').click(async function() {
            
            if (!confirm("Are you sure you want to delete this topic?")) {
                return;
            }

            let $tr = $(this).closest('tr');

            let id = $tr.data('id');

            try {

                let formData = new FormData();
                formData.append('id', id);

                let res = await ajaxCall({
                    url: baseUrl + 'admin/topics/delete',
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
    });
</script>
<?= $this->endSection() ?>